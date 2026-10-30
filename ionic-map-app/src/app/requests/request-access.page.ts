import { CommonModule } from '@angular/common';
import { ChangeDetectorRef, Component, inject } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { IonButton, IonContent, IonIcon, IonModal, IonSpinner } from '@ionic/angular';
import { addIcons } from 'ionicons';
import { arrowForwardOutline, checkmarkCircle, documentTextOutline } from 'ionicons/icons';
import { ApiService } from '../services/api.service';

interface RequestedService {
  process_id: number;
  title: string;
  office_name: string | null;
  classification: string | null;
  transaction_type: string | null;
  who_may_avail: string | null;
  total_fee: number | string;
  total_time: string | null;
  requirements: Array<{ requirement_name: string; where_to_secure: string }>;
  steps: Array<{ step_number: number; client_step: string; agency_action: string; fees: string; processing_time: string; person_responsible: string }>;
  persons: string[];
}

interface RequestAccessPayload {
  request: {
    request_id: number;
    status: 'pending' | 'accepted' | 'rejected';
    created_at: string;
    expires_at: string;
  };
  items: RequestedService[];
}

@Component({
  selector: 'app-request-access',
  standalone: true,
  templateUrl: './request-access.page.html',
  styleUrls: ['./request-access.page.scss'],
  imports: [CommonModule, IonButton, IonContent, IonIcon, IonModal, IonSpinner],
})
export class RequestAccessPage {
  private readonly api = inject(ApiService);
  private readonly changeDetector = inject(ChangeDetectorRef);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  request: RequestAccessPayload['request'] | null = null;
  items: RequestedService[] = [];
  selectedService: RequestedService | null = null;
  isLoading = true;
  errorMessage = '';
  isResending = false;
  resendMessage = '';
  canRequestResend = false;

  constructor() {
    addIcons({ arrowForwardOutline, checkmarkCircle, documentTextOutline });
    const token = this.route.snapshot.queryParamMap.get('token') ?? '';
    this.canRequestResend = /^[a-f0-9]{64}$/i.test(token);
    if (!token) {
      this.errorMessage = 'This request link is missing its access token.';
      this.isLoading = false;
      return;
    }

    this.api.get<RequestAccessPayload>('request-access', { token }).subscribe({
      next: (payload) => {
        this.request = payload.request;
        this.items = payload.items;
        this.isLoading = false;
        this.changeDetector.markForCheck();
      },
      error: (error: Error) => {
        this.errorMessage = error.message;
        this.isLoading = false;
        this.changeDetector.markForCheck();
      },
    });
  }

  requestNewLink(): void {
    if (!this.canRequestResend || this.isResending) return;
    const token = this.route.snapshot.queryParamMap.get('token') ?? '';
    this.isResending = true;
    this.resendMessage = '';
    this.api.post<{ ok: boolean; message: string }>('request-access', { token }).subscribe({
      next: ({ message }) => {
        this.resendMessage = message;
        this.isResending = false;
        this.changeDetector.markForCheck();
      },
      error: (error: Error) => {
        this.resendMessage = error.message || 'We could not process this request right now. Please try again later.';
        this.isResending = false;
        this.changeDetector.markForCheck();
      },
    });
  }

  continueAsGuest(): void {
    const returnUrl = `/tabs/services?requestToken=${encodeURIComponent(this.route.snapshot.queryParamMap.get('token') ?? '')}`;
    void this.router.navigate(['/login'], { queryParams: { returnUrl } });
  }

  openDetails(service: RequestedService): void {
    this.selectedService = service;
  }

  closeDetails(): void {
    this.selectedService = null;
  }

  officeColor(office: string | null): string {
    const palette = ['#dc2626', '#16a34a', '#2563eb', '#0891b2', '#c026d3', '#d97706', '#0f766e'];
    const name = (office ?? '').trim().toLowerCase();
    if (!name) return '#64748b';
    const hash = Array.from(name).reduce((total, character) => total + character.charCodeAt(0), 0);
    return palette[hash % palette.length];
  }

  requirementName(requirement: RequestedService['requirements'][number]): string {
    return requirement.requirement_name || 'Requirement';
  }

  stepText(step: RequestedService['steps'][number], field: 'client_step' | 'agency_action'): string {
    return step[field] || '-';
  }

  formatDateTime(value: string): string {
    const match = /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/.exec(value);
    if (!match) return value;

    const [, year, month, day, hourText, minute] = match;
    const hour = Number(hourText);
    const twelveHour = hour % 12 || 12;
    const date = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
      .format(new Date(Number(year), Number(month) - 1, Number(day)));
    return `${date}, ${twelveHour}:${minute} ${hour >= 12 ? 'PM' : 'AM'}`;
  }
}
