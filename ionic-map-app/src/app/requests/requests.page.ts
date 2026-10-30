import { CommonModule } from '@angular/common';
import { ChangeDetectorRef, Component, HostListener, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { IonButton, IonCheckbox, IonContent, IonIcon, IonInput, IonItem, IonLabel, IonModal, IonPopover, IonSearchbar, IonSpinner, ToastController } from '@ionic/angular';
import { addIcons } from 'ionicons';
import { arrowForwardOutline, checkmarkCircle, checkmarkCircleOutline, checkmarkOutline, chevronBackOutline, chevronDownOutline, chevronForwardOutline, closeOutline, documentTextOutline, lockClosedOutline, receiptOutline, timeOutline } from 'ionicons/icons';
import { ApiService } from '../services/api.service';
import { AuthService } from '../services/auth.service';
import { AccountHeaderComponent } from '../shared/account-header.component';

interface ServiceRequirement {
  requirement_name: string;
  where_to_secure: string;
}

interface ServiceStep {
  step_number: number;
  client_step: string;
  agency_action: string;
  fees: string;
  processing_time: string;
  person_responsible: string;
}

interface CampusService {
  process_id: number;
  title: string;
  office_name: string | null;
  classification: string | null;
  transaction_type: string | null;
  who_may_avail: string | null;
  total_fee: number | string;
  total_time: string | null;
  requirements: Array<ServiceRequirement | string>;
  steps: Array<ServiceStep | string>;
  persons: string[];
}

type ServicePayload = Omit<CampusService, 'requirements' | 'steps' | 'persons'> & {
  requirements?: Array<ServiceRequirement | string> | null;
  steps?: Array<ServiceStep | string> | null;
  persons?: string[] | null;
};

@Component({
  selector: 'app-requests',
  standalone: true,
  templateUrl: './requests.page.html',
  styleUrls: ['./requests.page.scss'],
  imports: [CommonModule, FormsModule, AccountHeaderComponent, IonButton, IonCheckbox, IonContent, IonIcon, IonInput, IonItem, IonLabel, IonModal, IonPopover, IonSearchbar, IonSpinner],
})
export class RequestsPage {
  private readonly officePalette = [
    '#2563eb', '#dc2626', '#16a34a', '#d97706', '#0891b2', '#7c3aed', '#c026d3',
    '#475569', '#65a30d', '#b91c1c', '#0f766e', '#9333ea', '#db2777', '#a16207',
    '#0284c7', '#4d7c0f', '#be123c', '#0e7490', '#6d28d9', '#c2410c',
  ];
  private readonly api = inject(ApiService);
  readonly authService = inject(AuthService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  private readonly changeDetector = inject(ChangeDetectorRef);
  private readonly toastController = inject(ToastController);

  @HostListener('window:guest-session-started')
  resetGuestPromptState(): void {
    this.guestLoginPromptOpen = false;
    this.guestLoginError = '';
    this.isSigningOutForLogin = false;
  }
  services: CampusService[] = [];
  isLoading = true;
  errorMessage = '';
  searchText = '';
  selectedOffice = '';
  currentPage = 1;
  readonly pageSize = 6;
  officePopoverOpen = false;
  selectedService: CampusService | null = null;
  guestLoginPromptOpen = false;
  guestLoginError = '';
  isSigningOutForLogin = false;
  readonly selectedRequestIds = new Set<number>();
  requestModalOpen = false;
  requestIdsPending: number[] = [];
  requesterFirstName = '';
  requesterMiddleName = '';
  requesterLastName = '';
  requesterSuffix = '';
  requesterEmail = '';
  requestError = '';
  requestSuccessMessage = '';
  isSubmittingRequest = false;
  requestShortcutToken = '';

  constructor() {
    addIcons({
      arrowForwardOutline,
      checkmarkCircle,
      checkmarkCircleOutline,
      checkmarkOutline,
      chevronBackOutline,
      chevronDownOutline,
      chevronForwardOutline,
      closeOutline,
      documentTextOutline,
      lockClosedOutline,
      receiptOutline,
      timeOutline,
    });
    this.requestShortcutToken = sessionStorage.getItem('documentRequestAccessToken') ?? '';
    this.route.queryParamMap.subscribe((params) => {
      const token = params.get('requestToken') ?? '';
      if (/^[a-f0-9]{64}$/i.test(token)) {
        this.requestShortcutToken = token;
        sessionStorage.setItem('documentRequestAccessToken', token);
      }
    });
    this.loadServices();
  }

  ionViewWillEnter(): void {
    this.resetGuestPromptState();
    this.selectedService = null;
  }

  get offices(): string[] {
    return [...new Set(this.services.map((service) => service.office_name).filter((office): office is string => Boolean(office)))].sort();
  }

  get filteredServices(): CampusService[] {
    const query = this.searchText.trim().toLowerCase();
    return this.services.filter((service) => {
      const matchesOffice = !this.selectedOffice || service.office_name === this.selectedOffice;
      const matchesSearch = !query || `${service.title} ${service.office_name ?? ''} ${service.who_may_avail ?? ''}`.toLowerCase().includes(query);
      return matchesOffice && matchesSearch;
    });
  }

  get totalPages(): number {
    return Math.ceil(this.filteredServices.length / this.pageSize);
  }

  get pageNumbers(): number[] {
    return Array.from({ length: this.totalPages }, (_, index) => index + 1);
  }

  get paginatedServices(): CampusService[] {
    const start = (this.currentPage - 1) * this.pageSize;
    return this.filteredServices.slice(start, start + this.pageSize);
  }

  loadServices(): void {
    this.isLoading = true;
    this.errorMessage = '';
    this.api.get<{ services: ServicePayload[] }>('services').subscribe({
      next: ({ services }) => {
        this.services = services.map((service) => this.normalizeService(service));
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

  setSearch(event: CustomEvent<{ value?: string | null }>): void {
    this.searchText = event.detail.value ?? '';
    this.currentPage = 1;
  }
  selectOffice(office: string): void {
    this.selectedOffice = office;
    this.currentPage = 1;
    this.officePopoverOpen = false;
  }

  goToPage(page: number): void {
    this.currentPage = Math.min(Math.max(page, 1), this.totalPages);
  }

  isRequestSelected(documentId: number): boolean {
    return this.selectedRequestIds.has(Number(documentId));
  }

  toggleRequestSelection(event: CustomEvent<{ checked?: boolean }>, documentId: number): void {
    if (event.detail.checked) this.selectedRequestIds.add(Number(documentId));
    else this.selectedRequestIds.delete(Number(documentId));
  }

  deselectAllRequests(): void {
    this.selectedRequestIds.clear();
  }

  requestOne(documentId: number): void {
    this.openRequestModal([documentId]);
  }

  requestSelected(): void {
    this.openRequestModal([...this.selectedRequestIds]);
  }

  requestAll(): void {
    this.openRequestModal(this.services.map((service) => service.process_id));
  }

  private openRequestModal(documentIds: number[]): void {
    this.requestIdsPending = [...new Set(documentIds.map(Number).filter((id) => id > 0))];
    if (!this.requestIdsPending.length) return;
    this.requestError = '';
    this.requestSuccessMessage = '';
    this.requestModalOpen = true;
  }

  closeRequestModal(): void {
    this.requestModalOpen = false;
    this.requestError = '';
  }

  openRequestShortcut(): void {
    if (this.requestShortcutToken) {
      void this.router.navigate(['/request-access'], { queryParams: { token: this.requestShortcutToken } });
    }
  }

  sanitizeName(value: string): string {
    return value.replace(/[^\p{L} ]/gu, '');
  }

  filterRequesterName(
    event: CustomEvent<{ value?: string | null }>,
    field: 'first' | 'middle' | 'last' | 'suffix',
  ): void {
    const input = event.target as HTMLIonInputElement;
    const rawValue = String(event.detail.value ?? '');
    const maxLength = field === 'suffix' ? 20 : 80;
    const sanitizedValue = this.sanitizeName(rawValue).slice(0, maxLength);

    if (input.value !== sanitizedValue) input.value = sanitizedValue;

    if (field === 'first') this.requesterFirstName = sanitizedValue;
    if (field === 'middle') this.requesterMiddleName = sanitizedValue;
    if (field === 'last') this.requesterLastName = sanitizedValue;
    if (field === 'suffix') this.requesterSuffix = sanitizedValue;
  }

  submitDocumentRequest(): void {
    if (this.isSubmittingRequest || !this.requestIdsPending.length) return;
    if (this.authService.currentUser()?.isGuest && (!this.requesterFirstName.trim() || !this.requesterLastName.trim() || !this.requesterEmail.trim())) {
      this.requestError = 'Enter your first name, last name, and email address.';
      void this.presentRequestToast(this.requestError, 'danger');
      return;
    }

    this.isSubmittingRequest = true;
    this.requestError = '';
    this.api.post<{ ok: boolean; document_count: number; email_sent?: boolean | null; email_pending?: boolean }>('document-requests', {
      document_ids: this.requestIdsPending,
      fname: this.requesterFirstName.trim(),
      mname: this.requesterMiddleName.trim(),
      lname: this.requesterLastName.trim(),
      suffix: this.requesterSuffix.trim(),
      requester_email: this.requesterEmail.trim(),
    }).subscribe({
      next: ({ document_count, email_sent, email_pending }) => {
        const emailMessage = email_sent === true
          ? ' Check your email for the private document link.'
          : email_pending
            ? ' Private link email delivery is processing separately.'
            : ' The request was saved, but the access email could not be sent.';
        this.requestSuccessMessage = `Request received for ${document_count} document${document_count === 1 ? '' : 's'}.${emailMessage}`;
        void this.presentRequestToast(this.requestSuccessMessage, 'success');
        this.selectedRequestIds.clear();
        this.requestIdsPending = [];
        this.requestModalOpen = false;
        this.requesterFirstName = '';
        this.requesterMiddleName = '';
        this.requesterLastName = '';
        this.requesterSuffix = '';
        this.requesterEmail = '';
        this.isSubmittingRequest = false;
        this.changeDetector.markForCheck();
      },
      error: (error: Error) => {
        this.requestError = error.message;
        void this.presentRequestToast(this.requestError, 'danger');
        this.isSubmittingRequest = false;
        this.changeDetector.markForCheck();
      },
    });
  }

  private async presentRequestToast(message: string, color: 'success' | 'danger'): Promise<void> {
    const toast = await this.toastController.create({
      message,
      color,
      duration: 6000,
      position: 'top',
      buttons: [{ text: 'Dismiss', role: 'cancel' }],
    });
    await toast.present();
  }

  toggleDetails(service: CampusService): void {
    if (this.authService.currentUser()?.isGuest) {
      this.guestLoginError = '';
      this.guestLoginPromptOpen = true;
      return;
    }

    const source = this.services.find((item) => Number(item.process_id) === Number(service.process_id)) ?? service;
    this.selectedService = this.normalizeService(source);
  }

  closeGuestLoginPrompt(): void {
    this.guestLoginPromptOpen = false;
    this.guestLoginError = '';
  }

  async continueToLogin(): Promise<void> {
    if (this.isSigningOutForLogin) return;

    this.guestLoginError = '';
    this.isSigningOutForLogin = true;
    try {
      await this.authService.logout();
      this.guestLoginPromptOpen = false;
      await this.router.navigateByUrl('/login?returnUrl=%2Ftabs%2Fservices');
    } catch (error) {
      this.guestLoginError = error instanceof Error ? error.message : 'Could not open sign in. Please try again.';
    } finally {
      this.isSigningOutForLogin = false;
    }
  }

  closeDetails(): void {
    this.selectedService = null;
  }

  @HostListener('document:keydown.escape')
  handleEscape(): void {
    this.closeDetails();
  }

  formatFee(value: number | string): string {
    const rawValue = String(value ?? '').trim();
    const amount = Number(rawValue);
    if (Number.isFinite(amount)) return amount === 0 ? 'Free' : `PHP ${amount.toFixed(2)}`;
    if (!rawValue || rawValue.toLowerCase() === 'none') return 'Free';
    return rawValue;
  }

  officeColor(office: string | null): string {
    const officeName = (office ?? '').trim();
    if (!officeName) return '#64748b';
    const officeIndex = this.offices.indexOf(officeName);
    return officeIndex >= 0 ? this.officePalette[officeIndex % this.officePalette.length] : '#64748b';
  }

  requirementName(requirement: ServiceRequirement | string): string {
    return typeof requirement === 'string' ? requirement : requirement.requirement_name;
  }

  requirementSource(requirement: ServiceRequirement | string): string {
    return typeof requirement === 'string' ? '' : requirement.where_to_secure;
  }

  stepField(step: ServiceStep | string, field: keyof ServiceStep): string {
    if (typeof step === 'string') return field === 'agency_action' ? step : '';
    return String(step[field] ?? '');
  }

  private normalizeService(service: ServicePayload): CampusService {
    const requirements = Array.isArray(service.requirements) ? service.requirements : [];
    const steps = Array.isArray(service.steps) ? service.steps : [];

    return {
      process_id: Number(service.process_id) || 0,
      title: String(service.title ?? ''),
      office_name: String(service.office_name ?? ''),
      classification: String(service.classification ?? ''),
      transaction_type: String(service.transaction_type ?? ''),
      who_may_avail: String(service.who_may_avail ?? ''),
      total_fee: service.total_fee ?? 'None',
      total_time: String(service.total_time ?? ''),
      requirements: requirements.map((requirement) => typeof requirement === 'string'
        ? requirement
        : {
            requirement_name: String(requirement.requirement_name ?? ''),
            where_to_secure: String(requirement.where_to_secure ?? ''),
          }),
      steps: steps.map((step) => typeof step === 'string'
        ? step
        : {
            step_number: Number(step.step_number) || 0,
            client_step: String(step.client_step ?? ''),
            agency_action: String(step.agency_action ?? ''),
            fees: String(step.fees ?? 'None'),
            processing_time: String(step.processing_time ?? ''),
            person_responsible: String(step.person_responsible ?? ''),
          }),
      persons: Array.isArray(service.persons) ? service.persons.map(String) : [],
    };
  }
}
