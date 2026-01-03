import './bootstrap';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import '@shopify/polaris/build/esm/styles.css';
import { Routes, Route, Navigate } from 'react-router-dom';
import { AppProvider, Frame } from '@shopify/polaris';
import en from '@shopify/polaris/locales/en.json';
import Navigation from './components/Navigation';
import Dashboard from './pages/Dashboard';
import ClipsList from './pages/ClipsList';
import ClipCreate from './pages/ClipCreate';
import ClipEdit from './pages/ClipEdit';
import CarouselsList from './pages/CarouselsList';
import CarouselEdit from './pages/CarouselEdit';
import Analytics from './pages/Analytics';
import Settings from './pages/Settings';

function App() {
  const shopDomain = new URLSearchParams(window.location.search).get('shop');

  return (
    <AppProvider i18n={en}>
      <Frame navigation={<Navigation />}>
        <Routes>
          <Route path="/" element={<Dashboard shop={shopDomain} />} />
          <Route path="/clips" element={<ClipsList shop={shopDomain} />} />
          <Route path="/clips/new" element={<ClipCreate shop={shopDomain} />} />
          <Route path="/clips/:id" element={<ClipEdit shop={shopDomain} />} />
          <Route path="/carousels" element={<CarouselsList shop={shopDomain} />} />
          <Route path="/carousels/:id" element={<CarouselEdit shop={shopDomain} />} />
          <Route path="/analytics" element={<Analytics shop={shopDomain} />} />
          <Route path="/settings" element={<Settings shop={shopDomain} />} />
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </Frame>
    </AppProvider>
  );
}

const container = document.getElementById('app');
if (container) {
  const root = createRoot(container);
  root.render(
    <BrowserRouter>
      <App />
    </BrowserRouter>
  );
}
