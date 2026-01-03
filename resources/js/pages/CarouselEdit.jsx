import React, { useEffect, useState } from 'react';
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
  Select,
  Checkbox,
  RangeSlider,
  ResourceList,
  ResourceItem,
  Thumbnail,
  Modal,
} from '@shopify/polaris';
import { DeleteIcon, DragHandleIcon } from '@shopify/polaris-icons';
import { api } from '../utils/api';

export default function CarouselEdit({ shop }) {
  const navigate = useNavigate();
  const { id } = useParams();

  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [carousel, setCarousel] = useState(null);
  const [availableClips, setAvailableClips] = useState([]);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [addClipsModal, setAddClipsModal] = useState(false);
  const [selectedClipIds, setSelectedClipIds] = useState([]);

  useEffect(() => {
    loadData();
  }, [id]);

  async function loadData() {
    try {
      setLoading(true);
      const [carouselData, clipsData] = await Promise.all([
        api.getCarousel(id),
        api.getClips(),
      ]);
      setCarousel(carouselData);

      // Filter out clips already in carousel
      const carouselClipIds = carouselData.clips?.map(c => c.id) || [];
      setAvailableClips(
        (clipsData.data || []).filter(c => !carouselClipIds.includes(c.id) && c.status === 'ready')
      );
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  async function handleSave() {
    try {
      setSaving(true);
      await api.updateCarousel(id, {
        name: carousel.name,
        display_location: carousel.display_location,
        is_active: carousel.is_active,
        autoplay: carousel.autoplay,
        autoplay_speed: carousel.autoplay_speed,
        show_metrics: carousel.show_metrics,
        items_desktop: carousel.items_desktop,
        items_tablet: carousel.items_tablet,
        items_mobile: carousel.items_mobile,
      });
      setSuccess('Carousel saved successfully');
      setTimeout(() => setSuccess(null), 3000);
    } catch (err) {
      setError(err.message);
    } finally {
      setSaving(false);
    }
  }

  async function handleAddClips() {
    try {
      await api.addClipsToCarousel(id, selectedClipIds);
      setAddClipsModal(false);
      setSelectedClipIds([]);
      loadData();
    } catch (err) {
      setError(err.message);
    }
  }

  async function handleRemoveClip(clipId) {
    try {
      await api.removeClipFromCarousel(id, clipId);
      loadData();
    } catch (err) {
      setError(err.message);
    }
  }

  function renderClipItem(clip) {
    return (
      <ResourceItem
        id={clip.id}
        media={
          <Thumbnail
            source={clip.thumbnail_url || 'https://via.placeholder.com/60x80'}
            alt={clip.title}
            size="medium"
          />
        }
        shortcutActions={[
          {
            content: 'Remove',
            destructive: true,
            onAction: () => handleRemoveClip(clip.id),
          },
        ]}
      >
        <InlineStack align="space-between">
          <div>
            <Text variant="bodyMd" fontWeight="bold">{clip.title}</Text>
            <Text variant="bodySm" tone="subdued">{clip.views_count} views</Text>
          </div>
          <InlineStack gap="200">
            <Badge tone={clip.is_published ? 'success' : undefined}>
              {clip.is_published ? 'Published' : 'Draft'}
            </Badge>
          </InlineStack>
        </InlineStack>
      </ResourceItem>
    );
  }

  function renderAvailableClipItem(clip) {
    const isSelected = selectedClipIds.includes(clip.id);

    return (
      <ResourceItem
        id={clip.id}
        onClick={() => {
          if (isSelected) {
            setSelectedClipIds(selectedClipIds.filter(id => id !== clip.id));
          } else {
            setSelectedClipIds([...selectedClipIds, clip.id]);
          }
        }}
        media={
          <Thumbnail
            source={clip.thumbnail_url || 'https://via.placeholder.com/60x80'}
            alt={clip.title}
            size="small"
          />
        }
      >
        <InlineStack align="space-between">
          <Text variant="bodyMd">{clip.title}</Text>
          <Checkbox checked={isSelected} onChange={() => {}} />
        </InlineStack>
      </ResourceItem>
    );
  }

  if (loading || !carousel) {
    return (
      <Page title="Loading..." backAction={{ content: 'Carousels', onAction: () => navigate('/carousels') }}>
        <Layout>
          <Layout.Section>
            <Card>
              <Text>Loading carousel...</Text>
            </Card>
          </Layout.Section>
        </Layout>
      </Page>
    );
  }

  return (
    <Page
      title={carousel.name}
      backAction={{ content: 'Carousels', onAction: () => navigate('/carousels') }}
      primaryAction={{
        content: 'Save',
        onAction: handleSave,
        loading: saving,
      }}
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
            <FormLayout>
              <InlineStack align="space-between">
                <Text variant="headingMd" as="h2">Carousel Settings</Text>
                <Badge tone={carousel.type === 'standard' ? 'info' : 'success'}>
                  {carousel.type}
                </Badge>
              </InlineStack>

              <TextField
                label="Name"
                value={carousel.name}
                onChange={(value) => setCarousel({ ...carousel, name: value })}
                autoComplete="off"
              />

              <Select
                label="Display Location"
                options={[
                  { label: 'Homepage', value: 'homepage' },
                  { label: 'Collection Pages', value: 'collection' },
                  { label: 'Product Pages', value: 'product' },
                  { label: 'All Pages', value: 'all' },
                ]}
                value={carousel.display_location}
                onChange={(value) => setCarousel({ ...carousel, display_location: value })}
              />

              <Checkbox
                label="Active"
                checked={carousel.is_active}
                onChange={(value) => setCarousel({ ...carousel, is_active: value })}
                helpText="When active, this carousel will be displayed on your store."
              />

              <Checkbox
                label="Autoplay"
                checked={carousel.autoplay}
                onChange={(value) => setCarousel({ ...carousel, autoplay: value })}
              />

              {carousel.autoplay && (
                <RangeSlider
                  label="Autoplay Speed (seconds)"
                  value={carousel.autoplay_speed}
                  onChange={(value) => setCarousel({ ...carousel, autoplay_speed: value })}
                  min={1}
                  max={30}
                  output
                />
              )}

              <Checkbox
                label="Show metrics (views, likes)"
                checked={carousel.show_metrics}
                onChange={(value) => setCarousel({ ...carousel, show_metrics: value })}
              />
            </FormLayout>
          </Card>

          <div style={{ marginTop: '16px' }}>
            <Card>
              <FormLayout>
                <Text variant="headingMd" as="h2">Items to Show</Text>

                <RangeSlider
                  label="Desktop"
                  value={carousel.items_desktop}
                  onChange={(value) => setCarousel({ ...carousel, items_desktop: value })}
                  min={1}
                  max={10}
                  output
                />

                <RangeSlider
                  label="Tablet"
                  value={carousel.items_tablet}
                  onChange={(value) => setCarousel({ ...carousel, items_tablet: value })}
                  min={1}
                  max={6}
                  output
                />

                <RangeSlider
                  label="Mobile"
                  value={carousel.items_mobile}
                  onChange={(value) => setCarousel({ ...carousel, items_mobile: value })}
                  min={1}
                  max={3}
                  output
                />
              </FormLayout>
            </Card>
          </div>
        </Layout.Section>

        <Layout.Section variant="oneHalf">
          <Card>
            <BlockStack gap="400">
              <InlineStack align="space-between">
                <Text variant="headingMd" as="h2">Clips ({carousel.clips?.length || 0})</Text>
                {carousel.type === 'custom' && (
                  <Button onClick={() => setAddClipsModal(true)}>Add Clips</Button>
                )}
              </InlineStack>

              {carousel.type === 'standard' ? (
                <Banner status="info">
                  Standard carousels automatically display all published clips, newest first.
                </Banner>
              ) : carousel.clips?.length === 0 ? (
                <Text tone="subdued">No clips added yet. Click "Add Clips" to select clips for this carousel.</Text>
              ) : (
                <ResourceList
                  items={carousel.clips || []}
                  renderItem={renderClipItem}
                />
              )}
            </BlockStack>
          </Card>
        </Layout.Section>
      </Layout>

      <Modal
        open={addClipsModal}
        onClose={() => { setAddClipsModal(false); setSelectedClipIds([]); }}
        title="Add Clips to Carousel"
        primaryAction={{
          content: `Add ${selectedClipIds.length} Clip${selectedClipIds.length !== 1 ? 's' : ''}`,
          onAction: handleAddClips,
          disabled: selectedClipIds.length === 0,
        }}
        secondaryActions={[
          { content: 'Cancel', onAction: () => { setAddClipsModal(false); setSelectedClipIds([]); } },
        ]}
      >
        <Modal.Section>
          {availableClips.length === 0 ? (
            <Text tone="subdued">No available clips. All clips are already in this carousel or not ready.</Text>
          ) : (
            <ResourceList
              items={availableClips}
              renderItem={renderAvailableClipItem}
            />
          )}
        </Modal.Section>
      </Modal>
    </Page>
  );
}
