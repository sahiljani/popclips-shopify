import React, { useEffect, useState } from 'react';
import {
  Page,
  Layout,
  Card,
  Text,
  Banner,
  Button,
  BlockStack,
  InlineStack,
  Badge,
  List,
  Divider,
  Box,
  SkeletonBodyText,
} from '@shopify/polaris';
import { api } from '../utils/api';

export default function Settings({ shop }) {
  const [loading, setLoading] = useState(true);
  const [subscription, setSubscription] = useState(null);
  const [error, setError] = useState(null);
  const [upgrading, setUpgrading] = useState(false);
  const [cancelling, setCancelling] = useState(false);

  useEffect(() => {
    loadSubscription();
  }, []);

  async function loadSubscription() {
    try {
      setLoading(true);
      const data = await api.getSubscription();
      setSubscription(data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  async function handleUpgrade() {
    try {
      setUpgrading(true);
      const response = await api.upgradeToPro();
      if (response.confirmation_url) {
        window.location.href = response.confirmation_url;
      }
    } catch (err) {
      setError(err.message);
      setUpgrading(false);
    }
  }

  async function handleCancel() {
    if (!confirm('Are you sure you want to cancel your Pro subscription?')) {
      return;
    }

    try {
      setCancelling(true);
      await api.cancelSubscription();
      loadSubscription();
    } catch (err) {
      setError(err.message);
    } finally {
      setCancelling(false);
    }
  }

  if (loading) {
    return (
      <Page title="Settings">
        <Layout>
          <Layout.Section>
            <Card>
              <SkeletonBodyText lines={4} />
            </Card>
          </Layout.Section>
        </Layout>
      </Page>
    );
  }

  const isPro = subscription?.plan === 'pro';
  const features = subscription?.features || {};
  const usage = subscription?.usage || {};

  return (
    <Page title="Settings">
      <Layout>
        {error && (
          <Layout.Section>
            <Banner status="critical" onDismiss={() => setError(null)}>
              {error}
            </Banner>
          </Layout.Section>
        )}

        <Layout.Section>
          <Card>
            <BlockStack gap="400">
              <InlineStack align="space-between">
                <div>
                  <Text variant="headingMd" as="h2">Subscription</Text>
                  <Text variant="bodySm" tone="subdued">Manage your Popclips plan</Text>
                </div>
                <Badge tone={isPro ? 'success' : 'info'} size="large">
                  {isPro ? 'Pro' : 'Free'}
                </Badge>
              </InlineStack>

              <Divider />

              {isPro ? (
                <BlockStack gap="400">
                  <Text variant="bodyMd">
                    You're on the Pro plan at <strong>$29.99/month</strong>
                  </Text>
                  <Button variant="plain" tone="critical" onClick={handleCancel} loading={cancelling}>
                    Cancel subscription
                  </Button>
                </BlockStack>
              ) : (
                <BlockStack gap="400">
                  <Text variant="bodyMd">
                    Upgrade to Pro to unlock custom carousels, advanced analytics, and more uploads.
                  </Text>
                  <Button variant="primary" onClick={handleUpgrade} loading={upgrading}>
                    Upgrade to Pro - $29.99/month
                  </Button>
                </BlockStack>
              )}
            </BlockStack>
          </Card>
        </Layout.Section>

        <Layout.Section variant="oneHalf">
          <Card>
            <BlockStack gap="400">
              <Text variant="headingMd" as="h2">Plan Features</Text>

              <List type="bullet">
                <List.Item>
                  Standard carousel: {features.standard_carousel ? '✓' : '✗'}
                </List.Item>
                <List.Item>
                  Custom carousels: {features.custom_carousels || 0}
                </List.Item>
                <List.Item>
                  Monthly uploads: {features.monthly_uploads || 0}
                </List.Item>
                <List.Item>
                  Basic analytics: {features.basic_analytics ? '✓' : '✗'}
                </List.Item>
                <List.Item>
                  Advanced analytics: {features.advanced_analytics ? '✓' : '✗'}
                </List.Item>
                <List.Item>
                  Product hotspots: {features.product_hotspots ? '✓' : '✗'}
                </List.Item>
                <List.Item>
                  Priority support: {features.priority_support ? '✓' : '✗'}
                </List.Item>
              </List>
            </BlockStack>
          </Card>
        </Layout.Section>

        <Layout.Section variant="oneHalf">
          <Card>
            <BlockStack gap="400">
              <Text variant="headingMd" as="h2">Current Usage</Text>

              <Box padding="400" background="bg-surface-secondary" borderRadius="200">
                <InlineStack align="space-between">
                  <Text>Monthly uploads</Text>
                  <Text fontWeight="bold">
                    {usage.monthly_uploads || 0} / {features.monthly_uploads || 0}
                  </Text>
                </InlineStack>
              </Box>

              <Box padding="400" background="bg-surface-secondary" borderRadius="200">
                <InlineStack align="space-between">
                  <Text>Custom carousels</Text>
                  <Text fontWeight="bold">
                    {usage.custom_carousels || 0} / {features.custom_carousels || 0}
                  </Text>
                </InlineStack>
              </Box>
            </BlockStack>
          </Card>
        </Layout.Section>

        <Layout.Section>
          <Card>
            <BlockStack gap="400">
              <Text variant="headingMd" as="h2">Compare Plans</Text>

              <div style={{ overflowX: 'auto' }}>
                <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                  <thead>
                    <tr style={{ borderBottom: '1px solid var(--p-color-border)' }}>
                      <th style={{ padding: '12px', textAlign: 'left' }}>Feature</th>
                      <th style={{ padding: '12px', textAlign: 'center' }}>Free</th>
                      <th style={{ padding: '12px', textAlign: 'center' }}>Pro ($29.99)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr style={{ borderBottom: '1px solid var(--p-color-border)' }}>
                      <td style={{ padding: '12px' }}>Standard Carousel</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>✓</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>✓</td>
                    </tr>
                    <tr style={{ borderBottom: '1px solid var(--p-color-border)' }}>
                      <td style={{ padding: '12px' }}>Custom Carousels</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>-</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>Up to 5</td>
                    </tr>
                    <tr style={{ borderBottom: '1px solid var(--p-color-border)' }}>
                      <td style={{ padding: '12px' }}>Video Uploads/Month</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>10</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>50</td>
                    </tr>
                    <tr style={{ borderBottom: '1px solid var(--p-color-border)' }}>
                      <td style={{ padding: '12px' }}>Basic Analytics</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>✓</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>✓</td>
                    </tr>
                    <tr style={{ borderBottom: '1px solid var(--p-color-border)' }}>
                      <td style={{ padding: '12px' }}>Advanced Analytics</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>-</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>✓</td>
                    </tr>
                    <tr style={{ borderBottom: '1px solid var(--p-color-border)' }}>
                      <td style={{ padding: '12px' }}>Product Hotspots</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>✓</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>✓</td>
                    </tr>
                    <tr>
                      <td style={{ padding: '12px' }}>Priority Support</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>-</td>
                      <td style={{ padding: '12px', textAlign: 'center' }}>✓</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </BlockStack>
          </Card>
        </Layout.Section>
      </Layout>
    </Page>
  );
}
