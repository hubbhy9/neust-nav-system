import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { Router } from '@angular/router';
import {
  IonButton,
  IonCard,
  IonCardContent,
  IonCardHeader,
  IonContent,
  IonHeader,
  IonItem,
  IonLabel,
  IonList,
  IonText,
  IonTitle,
  IonToolbar,
} from '@ionic/angular';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-profile',
  standalone: true,
  templateUrl: './profile.page.html',
  styleUrls: ['./profile.page.scss'],
  imports: [
    CommonModule,
    IonHeader,
    IonToolbar,
    IonTitle,
    IonContent,
    IonCard,
    IonCardHeader,
    IonCardContent,
    IonList,
    IonItem,
    IonLabel,
    IonButton,
    IonText,
  ],
})
export class ProfilePage {
  readonly authService = inject(AuthService);
  private readonly router = inject(Router);
  errorMessage = '';
  isLoggingOut = false;

  async logout(): Promise<void> {
    this.errorMessage = '';
    this.isLoggingOut = true;
    try {
      await this.authService.logout();
      await this.router.navigateByUrl('/login');
    } catch (error) {
      this.errorMessage = error instanceof Error ? error.message : 'Could not log out.';
    } finally {
      this.isLoggingOut = false;
    }
  }
}
