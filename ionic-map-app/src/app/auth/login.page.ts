import { HttpClient, HttpErrorResponse, HttpHeaders } from '@angular/common/http';
import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import {
  IonButton,
  IonCard,
  IonCardContent,
  IonCardHeader,
  IonCardTitle,
  IonContent,
  IonInput,
  IonItem,
  IonLabel,
  IonList,
  IonIcon,
  IonSpinner,
  IonText,
  ToastController,
} from '@ionic/angular';
import { addIcons } from 'ionicons';
import { alertCircleOutline, checkmarkCircleOutline, mailOpenOutline } from 'ionicons/icons';
import { AuthService } from '../services/auth.service';
import { environment } from '../../environments/environment';

interface PasswordResetResponse {
  success: boolean;
  message: string;
}

@Component({
  selector: 'app-login',
  standalone: true,
  templateUrl: './login.page.html',
  styleUrls: ['./login.page.scss'],
  imports: [
    FormsModule,
    IonContent,
    IonCard,
    IonCardHeader,
    IonCardTitle,
    IonCardContent,
    IonList,
    IonItem,
    IonLabel,
    IonInput,
    IonIcon,
    IonSpinner,
    IonButton,
    IonText,
  ],
})
export class LoginPage {
  private readonly http = inject(HttpClient);
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);
  private readonly toastController = inject(ToastController);

  email = '';
  password = '';
  readonly errorMessage = signal('');
  readonly isSubmitting = signal(false);
  readonly forgotPasswordOpen = signal(false);
  readonly resetOtpSent = signal(false);
  readonly resetComplete = signal(false);
  readonly isResettingPassword = signal(false);
  readonly resetMessage = signal('');
  readonly resetMessageIsError = signal(false);
  readonly keyboardOffset = signal(0);
  readonly keyboardAvailableHeight = signal(0);
  resetIdentifier = '';
  resetOtp = '';
  privacyAccepted = localStorage.getItem('neustPrivacyConsent') === '1';
  rememberConsent = false;
  showPassword = false;

  constructor() {
    addIcons({ alertCircleOutline, checkmarkCircleOutline, mailOpenOutline });
    void this.authService.initialize().then((authenticated) => {
      if (authenticated) {
        void this.router.navigateByUrl(this.destinationAfterLogin());
      }
    });
  }

  private destinationAfterLogin(): string {
    const returnUrl = this.route.snapshot.queryParamMap.get('returnUrl');
    return returnUrl?.startsWith('/') && !returnUrl.startsWith('//') ? returnUrl : '/tabs/home';
  }

  acceptPrivacy(): void {
    this.privacyAccepted = true;
    if (this.rememberConsent) {
      localStorage.setItem('neustPrivacyConsent', '1');
    }
  }

  togglePasswordVisibility(): void {
    this.showPassword = !this.showPassword;
  }

  openForgotPassword(): void {
    this.resetIdentifier = this.email.trim();
    this.resetOtp = '';
    this.resetOtpSent.set(false);
    this.resetComplete.set(false);
    this.resetMessage.set('');
    this.resetMessageIsError.set(false);
    this.forgotPasswordOpen.set(true);
    queueMicrotask(() => this.syncKeyboardOffset());
    requestAnimationFrame(() => this.syncKeyboardOffset());
    this.attachKeyboardListener();
  }

  closeForgotPassword(): void {
    if (this.isResettingPassword()) return;
    this.forgotPasswordOpen.set(false);
    this.resetOtp = '';
    this.resetOtpSent.set(false);
    this.resetComplete.set(false);
    this.resetMessage.set('');
    this.keyboardOffset.set(0);
    this.keyboardAvailableHeight.set(0);
    this.detachKeyboardListener();
  }

  private attachKeyboardListener(): void {
    const viewport = window.visualViewport;
    if (!viewport) return;

    viewport.removeEventListener('resize', this.updateKeyboardOffset);
    viewport.removeEventListener('scroll', this.updateKeyboardOffset);
    viewport.addEventListener('resize', this.updateKeyboardOffset);
    viewport.addEventListener('scroll', this.updateKeyboardOffset);
    this.updateKeyboardOffset();
  }

  private detachKeyboardListener(): void {
    const viewport = window.visualViewport;
    if (!viewport) return;

    viewport.removeEventListener('resize', this.updateKeyboardOffset);
    viewport.removeEventListener('scroll', this.updateKeyboardOffset);
  }

  private readonly updateKeyboardOffset = (): void => {
    const viewport = window.visualViewport;
    if (!viewport) {
      this.keyboardOffset.set(0);
      this.keyboardAvailableHeight.set(0);
      return;
    }

    const visibleBottom = viewport.offsetTop + viewport.height;
    const availableHeight = Math.max(0, viewport.height - 32);
    const sheet = document.querySelector<HTMLElement>('.forgot-password-sheet');
    this.keyboardAvailableHeight.set(availableHeight);

    if (!sheet) {
      this.keyboardOffset.set(0);
      requestAnimationFrame(() => {
        if (this.forgotPasswordOpen()) this.updateKeyboardOffset();
      });
      return;
    }

    const sheetHeight = sheet.offsetHeight;
    const centeredBottom = (window.innerHeight + sheetHeight) / 2;
    const centeredTop = (window.innerHeight - sheetHeight) / 2;
    const requiredLift = Math.max(0, centeredBottom + 16 - visibleBottom);
    const maximumLift = Math.max(0, centeredTop - viewport.offsetTop - 16);
    this.keyboardOffset.set(Math.min(requiredLift, maximumLift));
  };

  private syncKeyboardOffset(): void {
    this.updateKeyboardOffset();
  }

  async submitPasswordReset(): Promise<void> {
    if (this.resetComplete()) {
      this.closeForgotPassword();
      return;
    }

    const identifier = this.resetIdentifier.trim();
    if (!identifier) {
      await this.showResetFeedback('Enter your Gmail address.', true);
      return;
    }
    if (!/^[^\s@]+@gmail\.com$/i.test(identifier)) {
      await this.showResetFeedback('Use a valid Gmail address ending in @gmail.com.', true);
      return;
    }
    if (this.resetOtpSent() && !/^\d{6}$/.test(this.resetOtp.trim())) {
      await this.showResetFeedback('Enter the 6-digit OTP sent to your email.', true);
      return;
    }

    const action = this.resetOtpSent() ? 'verify_otp' : 'request_otp';
    const body = new URLSearchParams({ action, identifier, gmail_only: '1' });
    if (action === 'verify_otp') body.set('otp', this.resetOtp.trim());

    const requestStartedAt = performance.now();
    this.isResettingPassword.set(true);
    this.resetMessage.set('');
    this.resetMessageIsError.set(false);
    try {
      const result = await firstValueFrom(this.http.post<PasswordResetResponse>(
        `${environment.phpBaseUrl}/functions/forgot_password.php`,
        body.toString(),
        {
          headers: new HttpHeaders({ 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }),
          withCredentials: true,
        },
      ));
      if (!result.success) throw new Error(result.message || 'Password recovery failed.');

      await this.showResetFeedback(result.message, false);
      if (action === 'request_otp') {
        this.resetOtpSent.set(true);
      } else {
        this.resetComplete.set(true);
      }
    } catch (error) {
      const message = error instanceof HttpErrorResponse
        ? (typeof error.error?.message === 'string' ? error.error.message : 'Password recovery could not be completed. Try again.')
        : error instanceof Error ? error.message : 'Password recovery could not be completed. Try again.';
      await this.showResetFeedback(message, true);
    } finally {
      const remainingAnimationTime = 550 - (performance.now() - requestStartedAt);
      if (remainingAnimationTime > 0) {
        await new Promise((resolve) => setTimeout(resolve, remainingAnimationTime));
      }
      this.isResettingPassword.set(false);
    }
  }

  private async showResetFeedback(message: string, isError: boolean): Promise<void> {
    this.resetMessage.set(message);
    this.resetMessageIsError.set(isError);
    const toast = await this.toastController.create({
      message,
      color: isError ? 'danger' : 'success',
      duration: 5000,
      position: 'top',
      buttons: [{ text: 'Dismiss', role: 'cancel' }],
    });
    await toast.present();
  }

  async login(): Promise<void> {
    this.errorMessage.set('');
    this.isSubmitting.set(true);
    try {
      await this.authService.login(this.email, this.password);
      await this.router.navigateByUrl(this.destinationAfterLogin());
    } catch (error) {
      this.errorMessage.set(error instanceof Error ? error.message : 'Sign in failed. Try again.');
    } finally {
      this.isSubmitting.set(false);
    }
  }

  async continueAsGuest(): Promise<void> {
    if (!this.privacyAccepted || this.isSubmitting()) return;

    this.errorMessage.set('');
    this.isSubmitting.set(true);
    try {
      await this.authService.loginAsGuest();
      await this.router.navigateByUrl(this.destinationAfterLogin());
    } catch (error) {
      this.errorMessage.set(error instanceof Error ? error.message : 'Guest access could not be started. Try again.');
    } finally {
      this.isSubmitting.set(false);
    }
  }
}
