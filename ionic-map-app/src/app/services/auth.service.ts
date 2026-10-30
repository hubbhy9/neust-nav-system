import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { ApiService } from './api.service';

export interface AppUser {
  id: number;
  name: string;
  email: string;
  role: string;
  office: string;
  role_id: number;
  isAdmin: boolean;
  isGuest?: boolean;
}

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly api = inject(ApiService);
  private initialization: Promise<boolean> | null = null;

  readonly currentUser = signal<AppUser | null>(null);
  readonly isAuthenticated = signal(false);

  initialize(): Promise<boolean> {
    this.initialization ??= firstValueFrom(this.api.get<{ user: AppUser | null; csrfToken: string | null }>('session'))
      .then(({ user, csrfToken }) => {
        this.setUser(user);
        this.api.setCsrfToken(csrfToken);
        return Boolean(user);
      })
      .catch(() => {
        this.setUser(null);
        this.api.clearCsrfToken();
        return false;
      });
    return this.initialization;
  }

  async login(email: string, password: string): Promise<void> {
    const response = await firstValueFrom(this.api.post<{ user: AppUser; csrfToken: string }>('login', { email, password }));
    this.setUser(response.user);
    this.api.setCsrfToken(response.csrfToken);
    this.initialization = Promise.resolve(true);
  }

  async loginAsGuest(): Promise<void> {
    const response = await firstValueFrom(this.api.post<{ user: AppUser; csrfToken: string }>('guest', { privacyAccepted: true }));
    this.setUser(response.user);
    this.api.setCsrfToken(response.csrfToken);
    this.initialization = Promise.resolve(true);
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new Event('guest-session-started'));
    }
  }

  async logout(): Promise<void> {
    await firstValueFrom(this.api.post<{ ok: boolean }>('logout'));
    this.setUser(null);
    this.api.clearCsrfToken();
    this.initialization = Promise.resolve(false);
  }

  private setUser(user: AppUser | null): void {
    this.currentUser.set(user);
    this.isAuthenticated.set(Boolean(user));
  }
}
