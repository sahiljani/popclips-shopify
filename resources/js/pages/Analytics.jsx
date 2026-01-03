import React, { useEffect, useState } from 'react';
import {
  Page,
  Layout,
  Card,
  Text,
  Select,
  Banner,
  DataTable,
  BlockStack,
  InlineStack,
  Box,
  SkeletonBodyText,
} from '@shopify/polaris';
import { api } from '../utils/api';

function StatCard({ title, value, change, prefix = '' }) {
  const isPositive = change > 0;
  return (
    <Card>
      <BlockStack gap="200">
        <Text variant="bodyMd" as="span" tone="subdued">{title}</Text>
        <Text variant="headingXl" as="h3">{prefix}{value}</Text>
        {change !== undefined && change !== 0 && (
          <Text variant="bodySm" tone={isPositive ? 'success' : 'critical'}>
            {isPositive ? '+' : ''}{prefix}{change}
          </Text>
        )}
      </BlockStack>
    </Card>
  );
}

export default function Analytics({ shop }) {
  const [loading, setLoading] = useState(true);
  const [days, setDays] = useState('7');
  const [overview, setOverview] = useState(null);
  const [viewsData, setViewsData] = useState([]);
  const [topClips, setTopClips] = useState([]);
  const [topProducts, setTopProducts] = useState([]);
  const [error, setError] = useState(null);

  useEffect(() => {
    loadData();
  }, [days]);

  async function loadData() {
    try {
      setLoading(true);
      const [overviewData, views, clips, products] = await Promise.all([
        api.getOverview(parseInt(days)),
        api.getViewsOverTime(parseInt(days)),
        api.getTopClips(parseInt(days)),
        api.getTopProducts(parseInt(days)),
      ]);
      setOverview(overviewData);
      setViewsData(views);
      setTopClips(clips);
      setTopProducts(products);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  const clipsTableRows = topClips.map(clip => [
    clip.title,
    clip.views.toLocaleString(),
    `${clip.ctr}%`,
  ]);

  const productsTableRows = topProducts.map(product => [
    product.title,
    product.clicks.toLocaleString(),
    product.add_to_cart.toLocaleString(),
  ]);

  if (loading) {
    return (
      <Page title="Analytics">
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
    <Page title="Analytics">
      <Layout>
        {error && (
          <Layout.Section>
            <Banner status="critical" onDismiss={() => setError(null)}>
              {error}
            </Banner>
          </Layout.Section>
        )}

        <Layout.Section>
          <InlineStack align="end">
            <Select
              label="Time period"
              labelInline
              options={[
                { label: 'Last 7 days', value: '7' },
                { label: 'Last 14 days', value: '14' },
                { label: 'Last 30 days', value: '30' },
                { label: 'Last 90 days', value: '90' },
              ]}
              value={days}
              onChange={setDays}
            />
          </InlineStack>
        </Layout.Section>

        <Layout.Section>
          <Text variant="headingMd" as="h2">Overview</Text>
          <Box paddingBlockStart="400">
            <InlineStack gap="400" wrap={false}>
              <div style={{ flex: 1 }}>
                <StatCard
                  title="Total Views"
                  value={overview?.total_views?.toLocaleString() || '0'}
                  change={overview?.views_change}
                />
              </div>
              <div style={{ flex: 1 }}>
                <StatCard
                  title="Click-Through Rate"
                  value={`${overview?.ctr || 0}%`}
                />
              </div>
              <div style={{ flex: 1 }}>
                <StatCard
                  title="Add to Cart"
                  value={overview?.add_to_cart?.toLocaleString() || '0'}
                />
              </div>
              <div style={{ flex: 1 }}>
                <StatCard
                  title="Revenue"
                  value={overview?.revenue?.toLocaleString() || '0'}
                  change={overview?.revenue_change}
                  prefix="$"
                />
              </div>
            </InlineStack>
          </Box>
        </Layout.Section>

        <Layout.Section>
          <Card>
            <BlockStack gap="400">
              <Text variant="headingMd" as="h2">Views Over Time</Text>
              {viewsData.length === 0 ? (
                <Text tone="subdued">No view data available for this period.</Text>
              ) : (
                <div style={{ height: '200px', display: 'flex', alignItems: 'end', gap: '4px' }}>
                  {viewsData.map((item, index) => {
                    const maxCount = Math.max(...viewsData.map(v => v.count));
                    const height = maxCount > 0 ? (item.count / maxCount) * 150 : 0;
                    return (
                      <div
                        key={index}
                        style={{
                          flex: 1,
                          display: 'flex',
                          flexDirection: 'column',
                          alignItems: 'center',
                          gap: '4px',
                        }}
                      >
                        <div
                          style={{
                            width: '100%',
                            height: `${height}px`,
                            background: 'var(--p-color-bg-fill-brand)',
                            borderRadius: '4px 4px 0 0',
                          }}
                          title={`${item.count} views`}
                        />
                        <Text variant="bodySm" tone="subdued">
                          {new Date(item.date).toLocaleDateString('en-US', { weekday: 'short' })}
                        </Text>
                      </div>
                    );
                  })}
                </div>
              )}
            </BlockStack>
          </Card>
        </Layout.Section>

        <Layout.Section variant="oneHalf">
          <Card>
            <BlockStack gap="400">
              <Text variant="headingMd" as="h2">Top Performing Clips</Text>
              {topClips.length === 0 ? (
                <Text tone="subdued">No clip data available.</Text>
              ) : (
                <DataTable
                  columnContentTypes={['text', 'numeric', 'numeric']}
                  headings={['Clip', 'Views', 'CTR']}
                  rows={clipsTableRows}
                />
              )}
            </BlockStack>
          </Card>
        </Layout.Section>

        <Layout.Section variant="oneHalf">
          <Card>
            <BlockStack gap="400">
              <Text variant="headingMd" as="h2">Top Products</Text>
              {topProducts.length === 0 ? (
                <Text tone="subdued">No product data available.</Text>
              ) : (
                <DataTable
                  columnContentTypes={['text', 'numeric', 'numeric']}
                  headings={['Product', 'Clicks', 'Add to Cart']}
                  rows={productsTableRows}
                />
              )}
            </BlockStack>
          </Card>
        </Layout.Section>
      </Layout>
    </Page>
  );
}
