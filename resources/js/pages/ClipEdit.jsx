import React, { useEffect, useState, useRef } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
  Page,
  Layout,
  Card,
  FormLayout,
  TextField,
  Text,
  Banner,
  Button,
  BlockStack,
  InlineStack,
  Badge,
  Modal,
  Autocomplete,
  Icon,
  Thumbnail,
  Box,
  Select,
} from '@shopify/polaris';
import { SearchIcon, DeleteIcon, PlusIcon } from '@shopify/polaris-icons';
import { api } from '../utils/api';

export default function ClipEdit({ shop }) {
  const navigate = useNavigate();
  const { id } = useParams();
  const videoRef = useRef(null);

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [clip, setClip] = useState(null);
  const [hotspots, setHotspots] = useState([]);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  // Hotspot modal state
  const [hotspotModal, setHotspotModal] = useState(false);
  const [productSearch, setProductSearch] = useState('');
  const [productOptions, setProductOptions] = useState([]);
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [hotspotPosition, setHotspotPosition] = useState({ x: 50, y: 50 });
  const [hotspotTime, setHotspotTime] = useState({ start: 0, end: 5 });
  const [hotspotAnimation, setHotspotAnimation] = useState('pulse');

  useEffect(() => {
    loadClip();
  }, [id]);

  async function loadClip() {
    try {
      setLoading(true);
      const clipData = await api.getClip(id);
      setClip(clipData);
      setHotspots(clipData.hotspots || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  async function handleSave() {
    try {
      setSaving(true);
      await api.updateClip(id, {
        title: clip.title,
        description: clip.description,
      });
      setSuccess('Clip saved successfully');
      setTimeout(() => setSuccess(null), 3000);
    } catch (err) {
      setError(err.message);
    } finally {
      setSaving(false);
    }
  }

  async function handlePublish() {
    try {
      setSaving(true);
      if (clip.is_published) {
        await api.unpublishClip(id);
      } else {
        await api.publishClip(id);
      }
      await loadClip();
    } catch (err) {
      setError(err.message);
    } finally {
      setSaving(false);
    }
  }

  async function searchProducts(query) {
    if (query.length < 2) {
      setProductOptions([]);
      return;
    }

    try {
      const products = await api.searchProducts(query);
      setProductOptions(products.map(p => ({
        value: p.id.toString(),
        label: p.title,
        product: p,
      })));
    } catch (err) {
      console.error('Product search failed:', err);
    }
  }

  async function handleAddHotspot() {
    if (!selectedProduct) return;

    try {
      const newHotspot = await api.createHotspot(id, {
        shopify_product_id: selectedProduct.id,
        position_x: hotspotPosition.x,
        position_y: hotspotPosition.y,
        start_time: hotspotTime.start,
        end_time: hotspotTime.end,
        animation_style: hotspotAnimation,
      });

      setHotspots([...hotspots, newHotspot]);
      setHotspotModal(false);
      resetHotspotForm();
      setSuccess('Hotspot added successfully');
    } catch (err) {
      setError(err.message);
    }
  }

  async function handleDeleteHotspot(hotspotId) {
    try {
      await api.deleteHotspot(id, hotspotId);
      setHotspots(hotspots.filter(h => h.id !== hotspotId));
    } catch (err) {
      setError(err.message);
    }
  }

  function resetHotspotForm() {
    setSelectedProduct(null);
    setProductSearch('');
    setProductOptions([]);
    setHotspotPosition({ x: 50, y: 50 });
    setHotspotTime({ start: 0, end: 5 });
    setHotspotAnimation('pulse');
  }

  function handleVideoClick(e) {
    if (!hotspotModal) return;

    const rect = e.target.getBoundingClientRect();
    const x = ((e.clientX - rect.left) / rect.width) * 100;
    const y = ((e.clientY - rect.top) / rect.height) * 100;
    setHotspotPosition({ x: Math.round(x), y: Math.round(y) });
  }

  function openHotspotModal() {
    if (videoRef.current) {
      setHotspotTime({
        start: Math.floor(videoRef.current.currentTime),
        end: Math.min(Math.floor(videoRef.current.currentTime) + 5, clip.duration),
      });
    }
    setHotspotModal(true);
  }

  if (loading || !clip) {
    return (
      <Page title="Loading..." backAction={{ content: 'Clips', onAction: () => navigate('/clips') }}>
        <Layout>
          <Layout.Section>
            <Card>
              <Text>Loading clip...</Text>
            </Card>
          </Layout.Section>
        </Layout>
      </Page>
    );
  }

  return (
    <Page
      title={clip.title}
      backAction={{ content: 'Clips', onAction: () => navigate('/clips') }}
      primaryAction={{
        content: clip.is_published ? 'Unpublish' : 'Publish',
        onAction: handlePublish,
        loading: saving,
        disabled: clip.status !== 'ready',
      }}
      secondaryActions={[
        { content: 'Save', onAction: handleSave, loading: saving },
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

        {success && (
          <Layout.Section>
            <Banner status="success" onDismiss={() => setSuccess(null)}>
              {success}
            </Banner>
          </Layout.Section>
        )}

        <Layout.Section variant="oneHalf">
          <Card>
            <BlockStack gap="400">
              <InlineStack align="space-between">
                <Text variant="headingMd" as="h2">Video Preview</Text>
                <Badge tone={clip.status === 'ready' ? 'success' : 'attention'}>
                  {clip.status}
                </Badge>
              </InlineStack>

              <div
                style={{
                  position: 'relative',
                  aspectRatio: '9/16',
                  background: '#000',
                  borderRadius: '8px',
                  overflow: 'hidden',
                }}
              >
                <video
                  ref={videoRef}
                  src={clip.video_url}
                  poster={clip.thumbnail_url}
                  controls
                  style={{ width: '100%', height: '100%', objectFit: 'contain' }}
                  onClick={handleVideoClick}
                />

                {hotspots.map(hotspot => (
                  <div
                    key={hotspot.id}
                    style={{
                      position: 'absolute',
                      left: `${hotspot.position_x}%`,
                      top: `${hotspot.position_y}%`,
                      transform: 'translate(-50%, -50%)',
                      width: '32px',
                      height: '32px',
                      background: '#FF6B6B',
                      borderRadius: '50%',
                      display: 'flex',
                      alignItems: 'center',
                      justifyContent: 'center',
                      cursor: 'pointer',
                      border: '2px solid #fff',
                    }}
                    title={hotspot.product_title}
                  >
                    <span style={{ color: '#fff', fontSize: '12px' }}>+</span>
                  </div>
                ))}
              </div>

              <Button onClick={openHotspotModal} icon={PlusIcon} fullWidth>
                Add Product Hotspot
              </Button>
            </BlockStack>
          </Card>
        </Layout.Section>

        <Layout.Section variant="oneHalf">
          <BlockStack gap="400">
            <Card>
              <FormLayout>
                <Text variant="headingMd" as="h2">Clip Details</Text>

                <TextField
                  label="Title"
                  value={clip.title}
                  onChange={(value) => setClip({ ...clip, title: value })}
                  autoComplete="off"
                />

                <TextField
                  label="Description"
                  value={clip.description || ''}
                  onChange={(value) => setClip({ ...clip, description: value })}
                  multiline={3}
                  autoComplete="off"
                />

                <InlineStack gap="400">
                  <Text variant="bodySm" tone="subdued">
                    Duration: {clip.formatted_duration}
                  </Text>
                  <Text variant="bodySm" tone="subdued">
                    Views: {clip.views_count}
                  </Text>
                  <Text variant="bodySm" tone="subdued">
                    Likes: {clip.likes_count}
                  </Text>
                </InlineStack>
              </FormLayout>
            </Card>

            <Card>
              <BlockStack gap="400">
                <InlineStack align="space-between">
                  <Text variant="headingMd" as="h2">Tagged Products ({hotspots.length})</Text>
                </InlineStack>

                {hotspots.length === 0 ? (
                  <Text tone="subdued">No products tagged yet. Click "Add Product Hotspot" to get started.</Text>
                ) : (
                  <BlockStack gap="300">
                    {hotspots.map(hotspot => (
                      <Box key={hotspot.id} padding="300" background="bg-surface-secondary" borderRadius="200">
                        <InlineStack align="space-between">
                          <InlineStack gap="300">
                            <Thumbnail
                              source={hotspot.product_image || 'https://via.placeholder.com/40'}
                              alt={hotspot.product_title}
                              size="small"
                            />
                            <div>
                              <Text variant="bodySm" fontWeight="bold">{hotspot.product_title}</Text>
                              <Text variant="bodySm" tone="subdued">
                                {hotspot.formatted_time_range} • {hotspot.animation_style}
                              </Text>
                            </div>
                          </InlineStack>
                          <Button
                            icon={DeleteIcon}
                            variant="plain"
                            tone="critical"
                            onClick={() => handleDeleteHotspot(hotspot.id)}
                          />
                        </InlineStack>
                      </Box>
                    ))}
                  </BlockStack>
                )}
              </BlockStack>
            </Card>
          </BlockStack>
        </Layout.Section>
      </Layout>

      <Modal
        open={hotspotModal}
        onClose={() => { setHotspotModal(false); resetHotspotForm(); }}
        title="Add Product Hotspot"
        primaryAction={{
          content: 'Add Hotspot',
          onAction: handleAddHotspot,
          disabled: !selectedProduct,
        }}
        secondaryActions={[
          { content: 'Cancel', onAction: () => { setHotspotModal(false); resetHotspotForm(); } },
        ]}
      >
        <Modal.Section>
          <FormLayout>
            <Autocomplete
              options={productOptions}
              selected={selectedProduct ? [selectedProduct.id.toString()] : []}
              onSelect={(selected) => {
                const product = productOptions.find(p => p.value === selected[0]);
                setSelectedProduct(product?.product);
              }}
              textField={
                <Autocomplete.TextField
                  label="Search Products"
                  value={productSearch}
                  onChange={(value) => {
                    setProductSearch(value);
                    searchProducts(value);
                  }}
                  prefix={<Icon source={SearchIcon} />}
                  placeholder="Search for a product..."
                  autoComplete="off"
                />
              }
            />

            {selectedProduct && (
              <InlineStack gap="300">
                <Thumbnail
                  source={selectedProduct.image || 'https://via.placeholder.com/40'}
                  alt={selectedProduct.title}
                  size="small"
                />
                <div>
                  <Text variant="bodySm" fontWeight="bold">{selectedProduct.title}</Text>
                  <Text variant="bodySm" tone="subdued">${selectedProduct.price}</Text>
                </div>
              </InlineStack>
            )}

            <Text variant="bodySm" tone="subdued">
              Click on the video to set the hotspot position. Current: ({hotspotPosition.x}%, {hotspotPosition.y}%)
            </Text>

            <InlineStack gap="400">
              <TextField
                label="Start Time (seconds)"
                type="number"
                value={hotspotTime.start.toString()}
                onChange={(value) => setHotspotTime({ ...hotspotTime, start: parseFloat(value) || 0 })}
                autoComplete="off"
              />
              <TextField
                label="End Time (seconds)"
                type="number"
                value={hotspotTime.end.toString()}
                onChange={(value) => setHotspotTime({ ...hotspotTime, end: parseFloat(value) || 0 })}
                autoComplete="off"
              />
            </InlineStack>

            <Select
              label="Animation Style"
              options={[
                { label: 'Pulse', value: 'pulse' },
                { label: 'Static', value: 'static' },
                { label: 'Bounce', value: 'bounce' },
              ]}
              value={hotspotAnimation}
              onChange={setHotspotAnimation}
            />
          </FormLayout>
        </Modal.Section>
      </Modal>
    </Page>
  );
}
