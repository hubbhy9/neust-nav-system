import { Component } from '@angular/core';
import { IonIcon, IonRouterOutlet, IonTabBar, IonTabButton, IonTabs } from '@ionic/angular';
import { addIcons } from 'ionicons';
import { documentTextOutline, homeOutline, mapOutline } from 'ionicons/icons';

@Component({
  selector: 'app-tabs',
  standalone: true,
  templateUrl: './tabs.page.html',
  styleUrls: ['./tabs.page.scss'],
  imports: [IonTabs, IonRouterOutlet, IonTabBar, IonTabButton, IonIcon],
})
export class TabsPage {
  readonly tabs = [
    { id: 'home', label: 'Home', route: '/tabs/home', icon: 'home-outline' },
    { id: 'map', label: 'Map', route: '/tabs/map', icon: 'map-outline' },
    { id: 'services', label: 'Services', route: '/tabs/services', icon: 'document-text-outline' },
  ];

  constructor() {
    addIcons({ homeOutline, mapOutline, documentTextOutline });
  }
}
