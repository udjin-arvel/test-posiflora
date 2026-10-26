import { Routes } from '@angular/router';

import { OrdersPage } from './pages/orders/orders.page';

export const routes: Routes = [
  { path: '', component: OrdersPage },
  { path: '**', redirectTo: '' },
];
