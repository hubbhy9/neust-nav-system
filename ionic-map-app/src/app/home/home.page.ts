import { Component, inject } from '@angular/core';
import { Router } from '@angular/router';
import { IonButton, IonContent } from '@ionic/angular';
import { AccountHeaderComponent } from '../shared/account-header.component';

@Component({
  selector: 'app-home',
  standalone: true,
  templateUrl: './home.page.html',
  styleUrls: ['./home.page.scss'],
  imports: [AccountHeaderComponent, IonContent, IonButton],
})
export class HomePage {
  private readonly router = inject(Router);

  goToMap(): void {
    this.router.navigateByUrl('/tabs/map');
  }

  goToServices(): void {
    this.router.navigateByUrl('/tabs/services');
  }

  openDestination(destination: 'clinic' | 'registrar' | 'services'): void {
    if (destination === 'services') {
      void this.router.navigateByUrl('/tabs/services');
      return;
    }

    void this.router.navigate(['/tabs/map'], { queryParams: { target: `wp-${destination}` } });
  }
}
