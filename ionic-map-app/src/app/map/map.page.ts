import { CommonModule } from '@angular/common';
import { Component, ElementRef, HostListener, ViewChild, inject } from '@angular/core';
import { DomSanitizer, SafeResourceUrl } from '@angular/platform-browser';
import { ActivatedRoute } from '@angular/router';
import { IonContent, IonIcon } from '@ionic/angular';
import { addIcons } from 'ionicons';
import { contractOutline, expandOutline } from 'ionicons/icons';
import { AccountHeaderComponent } from '../shared/account-header.component';
import { AuthService } from '../services/auth.service';
import { ApiService } from '../services/api.service';

@Component({
  selector: 'app-map',
  standalone: true,
  templateUrl: './map.page.html',
  styleUrls: ['./map.page.scss'],
  imports: [CommonModule, AccountHeaderComponent, IonContent, IonIcon],
})
export class MapPage {
  private readonly sanitizer = inject(DomSanitizer);
  private readonly route = inject(ActivatedRoute);
  private readonly authService = inject(AuthService);
  private readonly api = inject(ApiService);
  private readonly mapOrigin = window.location.origin;
  private readonly workspaceMapPath = '/navigation/map-editor/map.php';
  readonly mapUrl: SafeResourceUrl = this.sanitizer.bypassSecurityTrustResourceUrl(
    `${this.mapOrigin}${this.workspaceMapPath}?ionic=1${this.authService.currentUser()?.isGuest ? '&guest=1' : ''}&v=20261022-1`,
  );

  @ViewChild('mapFrame') private mapFrame?: ElementRef<HTMLIFrameElement>;

  private highlightTarget: string | null = null;
  isLandscapeMode = false;

  constructor() {
    addIcons({ contractOutline, expandOutline });
  }

  ionViewWillEnter(): void {
    this.highlightTarget = this.route.snapshot.queryParamMap.get('target');
    this.sendMapHighlight();
  }

  onMapLoad(): void {
    this.sendMapHighlight();
  }

  async toggleLandscapeMode(): Promise<void> {
    const shell = this.mapFrame?.nativeElement.parentElement;
    if (!shell) return;

    if (this.isLandscapeMode) {
      if (document.fullscreenElement === shell) {
        await document.exitFullscreen().catch(() => undefined);
      }
      screen.orientation?.unlock?.();
      document.body.style.overflow = '';
      this.isLandscapeMode = false;
      return;
    }

    try {
      await shell.requestFullscreen?.();
    } catch {
      // Keep the in-app expanded fallback for browsers that reject fullscreen.
    }

    document.body.style.overflow = 'hidden';
    try {
      await screen.orientation?.lock?.('landscape');
    } catch {
      // Orientation lock is optional and unavailable in several mobile browsers.
    }

    this.isLandscapeMode = true;
  }

  @HostListener('document:fullscreenchange')
  onFullscreenChange(): void {
    if (!document.fullscreenElement && this.isLandscapeMode) {
      screen.orientation?.unlock?.();
      if (document.visibilityState === 'visible') {
        this.isLandscapeMode = false;
        document.body.style.overflow = '';
      }
    }
  }

  @HostListener('window:message', ['$event'])
  onMapActivity(event: MessageEvent): void {
    const frame = this.mapFrame?.nativeElement;
    const activity = event.data as { type?: string; event?: string; start?: string; destination?: string } | null;
    if (event.origin !== this.mapOrigin || event.source !== frame?.contentWindow || activity?.type !== 'ionic-audit') return;
    if (activity.event === 'MAP_DESTINATION' && typeof activity.destination === 'string') {
      this.api.post('activity', {
        event: 'MAP_DESTINATION',
        destination: activity.destination,
      }).subscribe({ error: () => undefined });
      return;
    }
    if (activity.event !== 'MAP_ROUTE' || typeof activity.start !== 'string' || typeof activity.destination !== 'string') return;

    this.api.post('activity', {
      event: 'MAP_ROUTE',
      start: activity.start,
      destination: activity.destination,
    }).subscribe({ error: () => undefined });
  }

  private sendMapHighlight(): void {
    if (!this.highlightTarget) return;

    this.mapFrame?.nativeElement.contentWindow?.postMessage({
      type: 'highlight-map-target',
      target: this.highlightTarget,
    }, this.mapOrigin);
  }
}
