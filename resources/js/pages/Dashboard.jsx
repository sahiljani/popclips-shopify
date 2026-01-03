import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Page,
  Layout,
  Card,
  Text,
  Button,
  InlineStack,
  BlockStack,
  Box,
  Icon,
  SkeletonBodyText,
  Banner,
} from '@shopify/polaris';
import {
  ViewIcon,
  ChartVerticalFilledIcon,
  CashDollarIcon,
  PlayCircleIcon,
  PlusIcon,
} from '@shopify/polaris-icons';
import { api } from '../utils/api';

function StatCard({ title, value, change, icon }) {
  const isPositive = change > 0;
  return (
    <Card>
      <BlockStack gap="200">
        <InlineStack align="space-between">
          <Text variant="bodyMd" as="span" tone="subdued">{title}</Text>
          <Icon source={icon} tone="subdued" />
        </InlineStack>
        <Text variant="headingXl" as="h3">{value}</Text>
        {change !== undefined && (
          <Text variant="bodySm" tone={isPositive ? 'success' : 'critical'}>
            {isPositive ? '+' : ''}{change}%
          </Text>
        )}
      </BlockStack>
    </Card>
  );
}

function ClipCard({ clip, onClick }) {
  return (
    <div
      onClick={onClick}
      style={{
        cursor: 'pointer',
        borderRadius: '8px',
        overflow: 'hidden',
        border: '1px solid var(--p-color-border)',
      }}
    >
      <div style={{ aspectRatio: '9/16', position: 'relative', background: '#000' }}>
        <img
          src={clip.thumbnail_url}
          alt={clip.title}
          style={{ width: '100%', height: '100%', objectFit: 'cover' }}
        />
        <div style={{
          position: 'absolute',
          bottom: 0,
          left: 0,
          right: 0,
          padding: '8px',
          background: 'linear-gradient(transparent, rgba(0,0,0,0.8))',
          color: '#fff',
        }}>
          <Text variant="bodySm" as="p">{clip.title}</Text>
          <InlineStack gap="200">
            <Text variant="bodySm">{clip.views_count} views</Text>
          </InlineStack>
        </div>
      </div>
    </div>
  );
}

export default function Dashboard({ shop }) {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState(null);
  const [clips, setClips] = useState([]);
  const [error, setError] = useState(null);

  useEffect(() => {
    loadData();
  }, []);

  async function loadData() {
    try {
      setLoading(true);
      const [overview, clipsData] = await Promise.all([
        api.getOverview(),
        api.getClips(),
      ]);
      setStats(overview);
      setClips(clipsData.data?.slice(0, 4) || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  if (loading) {
    return (
      <Page title="Dashboard">
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

  return (
    <Page
      title="Dashboard"
      primaryAction={{
        content: 'Create Clip',
        icon: PlusIcon,
        onAction: () => navigate('/clips/new'),
      }}
      secondaryActions={[
        {
          content: 'Create Carousel',
          onAction: () => navigate('/carousels'),
        },
      ]}
    >
      <Layout>
        {error && (
          <Layout.Section>
            <Banner status="critical" onDismiss={() => setError(null)}>
              {error}
            </Banner>
          </Layout.Section>
        )}

        <Layout.Section>
          <Text variant="headingMd" as="h2">Quick Stats</Text>
          <Box paddingBlockStart="400">
            <InlineStack gap="400" wrap={false}>
              <div style={{ flex: 1 }}>
                <StatCard
                  title="Total Views"
                  value={stats?.total_views?.toLocaleString() || '0'}
                  change={stats?.views_change}
                  icon={ViewIcon}
                />
              </div>
              <div style={{ flex: 1 }}>
                <StatCard
                  title="CTR"
                  value={`${stats?.ctr || 0}%`}
                  icon={ChartVerticalFilledIcon}
                />
              </div>
              <div style={{ flex: 1 }}>
                <StatCard
                  title="Revenue"
                  value={`$${stats?.revenue?.toLocaleString() || '0'}`}
                  change={stats?.revenue_change}
                  icon={CashDollarIcon}
                />
              </div>
              <div style={{ flex: 1 }}>
                <StatCard
                  title="Clips"
                  value={stats?.clips_count || '0'}
                  icon={PlayCircleIcon}
                />
              </div>
            </InlineStack>
          </Box>
        </Layout.Section>

        <Layout.Section>
          <InlineStack align="space-between">
            <Text variant="headingMd" as="h2">Recent Clips</Text>
            <Button variant="plain" onClick={() => navigate('/clips')}>View all</Button>
          </InlineStack>
          <Box paddingBlockStart="400">
            {clips.length === 0 ? (
              <Card>
                <BlockStack gap="400" align="center">
                  <Icon source={PlayCircleIcon} tone="subdued" />
                  <Text variant="bodyMd" tone="subdued">No clips yet</Text>
                  <Button onClick={() => navigate('/clips/new')}>Create your first clip</Button>
                </BlockStack>
              </Card>
            ) : (
              <InlineStack gap="400" wrap={false}>
                {clips.map((clip) => (
                  <div key={clip.id} style={{ width: '180px' }}>
                    <ClipCard
                      clip={clip}
                      onClick={() => navigate(`/clips/${clip.id}`)}
                    />
                  </div>
                ))}
              </InlineStack>
            )}
          </Box>
        </Layout.Section>
      </Layout>
    </Page>
  );
}
