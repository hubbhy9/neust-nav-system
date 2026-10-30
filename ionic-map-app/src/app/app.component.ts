import { Component, inject } from '@angular/core';
import { IonApp, IonRouterOutlet } from '@ionic/angular';
import { NavigationEnd, Router } from '@angular/router';
import { filter } from 'rxjs';
import { ApiService } from './services/api.service';
import { AuthService } from './services/auth.service';

@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  imports: [IonApp, IonRouterOutlet],
})
export class AppComponent {
  private readonly router = inject(Router);
  private readonly api = inject(ApiService);
  private readonly authService = inject(AuthService);

  constructor() {
    this.router.events.pipe(filter((event): event is NavigationEnd => event instanceof NavigationEnd)).subscribe((event) => {
      const page = event.urlAfterRedirects.split(/[?#]/, 1)[0].replace(/^\/+|\/+$/g, '') || 'home';
      void this.authService.initialize().then((authenticated) => {
        if (authenticated) this.api.post('activity', { event: 'PAGE_VIEW', page }).subscribe({ error: () => undefined });
      });
    });
  }
}
