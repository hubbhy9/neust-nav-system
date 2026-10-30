import { HttpClient, HttpErrorResponse, HttpHeaders } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../environments/environment';

@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = environment.apiUrl;
  private csrfToken = '';

  get<T>(action: string, params?: Record<string, string | number>): Observable<T> {
    return this.http.get<T>(this.url(action, params), { withCredentials: true }).pipe(catchError(this.toApiError));
  }

  post<T>(action: string, body: unknown = {}): Observable<T> {
    return this.http.post<T>(this.url(action), body, {
      withCredentials: true,
      headers: this.writeHeaders(),
    }).pipe(catchError(this.toApiError));
  }

  patch<T>(action: string, body: unknown): Observable<T> {
    return this.http.patch<T>(this.url(action), body, {
      withCredentials: true,
      headers: this.writeHeaders(),
    }).pipe(catchError(this.toApiError));
  }

  delete<T>(action: string, body: unknown): Observable<T> {
    return this.http.delete<T>(this.url(action), {
      body,
      withCredentials: true,
      headers: this.writeHeaders(),
    }).pipe(catchError(this.toApiError));
  }

  setCsrfToken(token: string | null | undefined): void {
    this.csrfToken = token ?? '';
  }

  clearCsrfToken(): void {
    this.csrfToken = '';
  }

  private url(action: string, params?: Record<string, string | number>): string {
    const query = new URLSearchParams({ action });
    Object.entries(params ?? {}).forEach(([key, value]) => query.set(key, String(value)));
    return `${this.baseUrl}?${query.toString()}`;
  }

  private writeHeaders(): HttpHeaders {
    let headers = new HttpHeaders({ 'Content-Type': 'application/json' });
    if (this.csrfToken) {
      headers = headers.set('X-CSRF-Token', this.csrfToken);
    }
    return headers;
  }

  private readonly toApiError = (error: HttpErrorResponse) => {
    const message = typeof error.error?.error === 'string'
      ? error.error.error
      : error.status === 0
        ? 'Cannot reach the PHP server. Check that XAMPP is running.'
        : 'The request could not be completed.';
    return throwError(() => new Error(message));
  };
}
