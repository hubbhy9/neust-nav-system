import { inject } from '@angular/core';
import { CanActivateChildFn, Router, Routes } from '@angular/router';
import { AuthService } from './services/auth.service';

const requireAuthentication: CanActivateChildFn = (_route, state) => {
  const authService = inject(AuthService);
  const router = inject(Router);
  return authService.initialize().then((authenticated) => authenticated
    ? true
    : router.createUrlTree(['/login'], { queryParams: { returnUrl: state.url } }));
};

export const routes: Routes = [
  {
    path: '',
    redirectTo: 'login',
    pathMatch: 'full',
  },
  {
    path: 'login',
    loadComponent: () => import('./auth/login.page').then((m) => m.LoginPage),
  },
  {
    path: 'request-access',
    loadComponent: () => import('./requests/request-access.page').then((m) => m.RequestAccessPage),
  },
  {
    path: 'tabs',
    loadComponent: () => import('./tabs/tabs.page').then((m) => m.TabsPage),
    canActivateChild: [requireAuthentication],
    children: [
      { path: '', redirectTo: 'home', pathMatch: 'full' },
      { path: 'home', loadComponent: () => import('./home/home.page').then((m) => m.HomePage) },
      { path: 'map', loadComponent: () => import('./map/map.page').then((m) => m.MapPage) },
      { path: 'services', loadComponent: () => import('./requests/requests.page').then((m) => m.RequestsPage) },
    ],
  },
  {
    path: 'home',
    redirectTo: 'tabs/home',
    pathMatch: 'full',
  },
  {
    path: 'map',
    redirectTo: 'tabs/map',
    pathMatch: 'full',
  },
  {
    path: 'services',
    redirectTo: 'tabs/services',
    pathMatch: 'full',
  },
  {
    path: 'requests',
    redirectTo: 'tabs/services',
    pathMatch: 'full',
  },
  { path: '**', redirectTo: 'login' },
];
