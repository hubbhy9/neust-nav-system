import { Component, ElementRef, HostListener, inject, input } from '@angular/core';
import { Router } from '@angular/router';
import { IonButtons, IonHeader, IonToolbar } from '@ionic/angular';
import { AuthService } from '../services/auth.service';

@Component({
  selector: 'app-account-header',
  standalone: true,
  templateUrl: './account-header.component.html',
  styleUrl: './account-header.component.scss',
  imports: [IonButtons, IonHeader, IonToolbar],
})
export class AccountHeaderComponent {
  readonly title = input('NEUST Smart Campus');
  readonly authService = inject(AuthService);
  private readonly router = inject(Router);
  private readonly element = inject(ElementRef<HTMLElement>);
  isMenuOpen = false;

  toggleMenu(): void {
    this.isMenuOpen = !this.isMenuOpen;
  }

  @HostListener('document:click', ['$event'])
  closeMenuOutside(event: MouseEvent): void {
    if (!this.element.nativeElement.contains(event.target as Node)) {
      this.isMenuOpen = false;
    }
  }

  @HostListener('document:keydown.escape')
  closeMenuOnEscape(): void {
    this.isMenuOpen = false;
  }

  async signOut(): Promise<void> {
    await this.authService.logout();
    this.isMenuOpen = false;
    await this.router.navigateByUrl('/login');
  }
}