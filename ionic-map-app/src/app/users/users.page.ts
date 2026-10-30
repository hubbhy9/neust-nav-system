import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { IonButton, IonContent, IonHeader, IonInput, IonItem, IonLabel, IonSelect, IonSelectOption, IonText, IonTitle, IonToolbar } from '@ionic/angular';
import { ApiService } from '../services/api.service';
import { AuthService } from '../services/auth.service';

interface ManagedUser {
  id: number;
  registration_number: string | null;
  student_number: string | null;
  first_name: string;
  middle_name: string | null;
  last_name: string;
  suffix: string | null;
  email: string;
  mobile_number: string | null;
  role_id: number;
  role: string;
}

interface ManagedRole {
  role_id: number;
  role_name: string;
}

@Component({
  selector: 'app-users',
  standalone: true,
  templateUrl: './users.page.html',
  styleUrls: ['./users.page.scss'],
  imports: [CommonModule, FormsModule, IonButton, IonContent, IonHeader, IonInput, IonItem, IonLabel, IonSelect, IonSelectOption, IonText, IonTitle, IonToolbar],
})
export class UsersPage {
  private readonly api = inject(ApiService);
  readonly authService = inject(AuthService);
  users: ManagedUser[] = [];
  roles: ManagedRole[] = [];
  searchText = '';
  isLoading = true;
  isSaving = false;
  errorMessage = '';
  successMessage = '';
  temporaryPassword = '';
  editingId: number | null = null;
  firstName = '';
  middleName = '';
  lastName = '';
  suffix = '';
  email = '';
  studentNumber = '';
  registrationNumber = '';
  roleId: number | null = null;

  constructor() {
    this.loadUsers();
    this.loadRoles();
  }

  get filteredUsers(): ManagedUser[] {
    const query = this.searchText.trim().toLowerCase();
    return this.users.filter((user) => !query || `${user.first_name} ${user.middle_name ?? ''} ${user.last_name} ${user.email} ${user.student_number ?? ''} ${user.role}`.toLowerCase().includes(query));
  }

  loadUsers(): void {
    this.isLoading = true;
    this.api.get<{ users: ManagedUser[] }>('users').subscribe({
      next: ({ users }) => { this.users = users; this.isLoading = false; },
      error: (error: Error) => { this.errorMessage = error.message; this.isLoading = false; },
    });
  }

  private loadRoles(): void {
    this.api.get<{ roles: ManagedRole[] }>('roles').subscribe({
      next: ({ roles }) => { this.roles = roles; },
      error: (error: Error) => { this.errorMessage = error.message; },
    });
  }

  saveUser(): void {
    this.errorMessage = '';
    this.successMessage = '';
    this.temporaryPassword = '';
    this.isSaving = true;
    const userData = {
      first_name: this.firstName,
      middle_name: this.middleName,
      last_name: this.lastName,
      suffix: this.suffix,
      email: this.email,
      student_number: this.studentNumber,
      registration_number: this.registrationNumber,
    };
    const request = this.editingId
      ? this.api.patch<{ ok: boolean; temporaryPassword?: string }>('users', { ...userData, id: this.editingId, ...(this.roleId === null ? {} : { role_id: this.roleId }) })
      : this.api.post<{ ok: boolean; temporaryPassword?: string }>('users', userData);

    request.subscribe({
      next: (result) => {
        this.successMessage = this.editingId ? 'User updated.' : 'User created. Share the temporary password securely.';
        this.temporaryPassword = result.temporaryPassword ?? '';
        this.resetForm();
        this.isSaving = false;
        this.loadUsers();
      },
      error: (error: Error) => { this.errorMessage = error.message; this.isSaving = false; },
    });
  }

  editUser(user: ManagedUser): void {
    this.editingId = user.id;
    this.firstName = user.first_name;
    this.middleName = user.middle_name ?? '';
    this.lastName = user.last_name;
    this.suffix = user.suffix ?? '';
    this.email = user.email;
    this.studentNumber = user.student_number ?? '';
    this.registrationNumber = user.registration_number ?? '';
    this.roleId = user.role_id;
    this.errorMessage = '';
    this.successMessage = '';
  }

  deleteUser(user: ManagedUser): void {
    if (!confirm(`Delete ${user.first_name} ${user.last_name}? This cannot be undone.`)) return;
    this.api.delete<{ ok: boolean }>('users', { id: user.id }).subscribe({
      next: () => { this.successMessage = 'User deleted.'; this.loadUsers(); },
      error: (error: Error) => { this.errorMessage = error.message; },
    });
  }

  cancelEdit(): void { this.resetForm(); }

  private resetForm(): void {
    this.editingId = null;
    this.firstName = '';
    this.middleName = '';
    this.lastName = '';
    this.suffix = '';
    this.email = '';
    this.studentNumber = '';
    this.registrationNumber = '';
    this.roleId = null;
  }
}
