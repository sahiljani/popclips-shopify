import React from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { Navigation as PolarisNavigation } from '@shopify/polaris';
import {
  HomeIcon,
  PlayCircleIcon,
  ListBulletedIcon,
  ChartVerticalFilledIcon,
  SettingsIcon,
} from '@shopify/polaris-icons';

export default function Navigation() {
  const location = useLocation();
  const navigate = useNavigate();

  const items = [
    {
      label: 'Dashboard',
      icon: HomeIcon,
      url: '/',
      selected: location.pathname === '/',
      onClick: () => navigate('/'),
    },
    {
      label: 'Clips',
      icon: PlayCircleIcon,
      url: '/clips',
      selected: location.pathname.startsWith('/clips'),
      onClick: () => navigate('/clips'),
    },
    {
      label: 'Carousels',
      icon: ListBulletedIcon,
      url: '/carousels',
      selected: location.pathname.startsWith('/carousels'),
      onClick: () => navigate('/carousels'),
    },
    {
      label: 'Analytics',
      icon: ChartVerticalFilledIcon,
      url: '/analytics',
      selected: location.pathname === '/analytics',
      onClick: () => navigate('/analytics'),
    },
    {
      label: 'Settings',
      icon: SettingsIcon,
      url: '/settings',
      selected: location.pathname === '/settings',
      onClick: () => navigate('/settings'),
    },
  ];

  return (
    <PolarisNavigation location={location.pathname}>
      <PolarisNavigation.Section
        title="Popclips"
        items={items}
      />
    </PolarisNavigation>
  );
}
