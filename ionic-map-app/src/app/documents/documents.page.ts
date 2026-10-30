import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { IonButton, IonContent, IonInput, IonItem, IonLabel, IonText, IonTextarea } from '@ionic/angular';
import { ApiService } from '../services/api.service';
import { AccountHeaderComponent } from '../shared/account-header.component';

interface DocumentProcess {
  process_id: number;
  title: string;
  office_name: string;
  who_may_avail: string | null;
  total_fee: number | string;
  total_time: string | null;
  requirements: string[];
  steps: string[];
  persons: string[];
}

@Component({
  selector: 'app-documents',
  standalone: true,
  templateUrl: './documents.page.html',
  styleUrls: ['./documents.page.scss'],
  imports: [CommonModule, FormsModule, AccountHeaderComponent, IonButton, IonContent, IonInput, IonItem, IonLabel, IonText, IonTextarea],
})
export class DocumentsPage {
  private readonly api = inject(ApiService);
  documents: DocumentProcess[] = [];
  isLoading = true;
  isSaving = false;
  errorMessage = '';
  successMessage = '';
  editingId: number | null = null;
  title = '';
  officeName = '';
  audience = '';
  fee: number | null = 0;
  processingTime = '';
  requirementsText = '';
  stepsText = '';
  personsText = '';

  constructor() { this.loadDocuments(); }

  loadDocuments(): void {
    this.isLoading = true;
    this.api.get<{ documents: DocumentProcess[] }>('documents').subscribe({
      next: ({ documents }) => { this.documents = documents; this.isLoading = false; },
      error: (error: Error) => { this.errorMessage = error.message; this.isLoading = false; },
    });
  }

  editDocument(document: DocumentProcess): void {
    this.editingId = document.process_id;
    this.title = document.title;
    this.officeName = document.office_name;
    this.audience = document.who_may_avail ?? '';
    this.fee = Number(document.total_fee);
    this.processingTime = document.total_time ?? '';
    this.requirementsText = document.requirements.join('\n');
    this.stepsText = document.steps.join('\n');
    this.personsText = document.persons.join('\n');
    this.errorMessage = '';
    this.successMessage = '';
  }

  saveDocument(): void {
    this.isSaving = true;
    this.errorMessage = '';
    this.successMessage = '';
    const data = {
      title: this.title,
      office_name: this.officeName,
      who_may_avail: this.audience,
      total_fee: this.fee ?? 0,
      total_time: this.processingTime,
      requirements: this.toLines(this.requirementsText),
      steps: this.toLines(this.stepsText),
      persons: this.toLines(this.personsText),
    };
    const request = this.editingId
      ? this.api.patch<{ ok: boolean }>('documents', { ...data, process_id: this.editingId })
      : this.api.post<{ ok: boolean; process_id: number }>('documents', data);
    request.subscribe({
      next: () => {
        this.successMessage = this.editingId ? 'Document process updated.' : 'Document process added.';
        this.resetForm();
        this.isSaving = false;
        this.loadDocuments();
      },
      error: (error: Error) => { this.errorMessage = error.message; this.isSaving = false; },
    });
  }

  deleteDocument(document: DocumentProcess): void {
    if (!confirm(`Delete “${document.title}”? This cannot be undone.`)) return;
    this.api.delete<{ ok: boolean }>('documents', { process_id: document.process_id }).subscribe({
      next: () => { this.successMessage = 'Document process deleted.'; this.loadDocuments(); },
      error: (error: Error) => { this.errorMessage = error.message; },
    });
  }

  cancelEdit(): void { this.resetForm(); }

  private resetForm(): void {
    this.editingId = null;
    this.title = '';
    this.officeName = '';
    this.audience = '';
    this.fee = 0;
    this.processingTime = '';
    this.requirementsText = '';
    this.stepsText = '';
    this.personsText = '';
  }

  private toLines(value: string): string[] {
    return value.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
  }
}
