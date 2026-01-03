import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Page,
  Layout,
  Card,
  ResourceList,
  ResourceItem,
  Text,
  Badge,
  Banner,
  EmptyState,
  Modal,
  FormLayout,
  TextField,
  Select,
  InlineStack,
} from '@shopify/polaris';
import { PlusIcon } from '@shopify/polaris-icons';
import { api } from '../utils/api';

export default function CarouselsList({ shop }) {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [carousels, setCarousels] = useState([]);
  const [error, setError] = useState(null);
  const [createModal, setCreateModal] = useState(false);
  const [newCarousel, setNewCarousel] = useState({
    name: '',
    type: 'custom',
    display_location: 'homepage',
  });

  useEffect(() => {
    loadCarousels();
  }, []);

  async function loadCarousels() {
    try {
      setLoading(true);
      const response = await api.getCarousels();
      setCarousels(response);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  async function handleCreate() {
    try {
      const carousel = await api.createCarousel(newCarousel);
      setCarousels([...carousels, carousel]);
      setCreateModal(false);
      setNewCarousel({ name: '', type: 'custom', display_location: 'homepage' });
      navigate(`/carousels/${carousel.id}`);
    } catch (err) {
      setError(err.message);
    }
  }

  async function handleToggleActive(carouselId, isActive) {
    try {
      await api.updateCarousel(carouselId, { is_active: !isActive });
      loadCarousels();
    } catch (err) {
      setError(err.message);
    }
  }

  function renderItem(carousel) {
    const { id, name, type, display_location, is_active, clips_count } = carousel;

    return (
      <ResourceItem
        id={id}
        onClick={() => navigate(`/carousels/${id}`)}
        shortcutActions={[
          {
            content: is_active ? 'Deactivate' : 'Activate',
            onAction: () => handleToggleActive(id, is_active),
          },
        ]}
      >
        <InlineStack align="space-between">
          <div>
            <Text variant="bodyMd" fontWeight="bold" as="h3">{name}</Text>
            <Text variant="bodySm" tone="subdued">
              {clips_count} clips • {display_location}
            </Text>
          </div>
          <InlineStack gap="200">
            <Badge tone={type === 'standard' ? 'info' : 'success'}>
              {type === 'standard' ? 'Standard' : 'Custom'}
            </Badge>
            <Badge tone={is_active ? 'success' : undefined}>
              {is_active ? 'Active' : 'Inactive'}
            </Badge>
          </InlineStack>
        </InlineStack>
      </ResourceItem>
    );
  }

  const emptyState = (
    <EmptyState
      heading="Create your first carousel"
      action={{
        content: 'Create Carousel',
        onAction: () => setCreateModal(true),
      }}
      image="https://cdn.shopify.com/s/files/1/0262/4071/2726/files/emptystate-files.png"
    >
      <p>Organize your clips into carousels to display on your store.</p>
    </EmptyState>
  );

  return (
    <Page
      title="Carousels"
      primaryAction={{
        content: 'Create Carousel',
        icon: PlusIcon,
        onAction: () => setCreateModal(true),
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

        <Layout.Section>
          <Card padding="0">
            <ResourceList
              loading={loading}
              items={carousels}
              renderItem={renderItem}
              emptyState={emptyState}
            />
          </Card>
        </Layout.Section>
      </Layout>

      <Modal
        open={createModal}
        onClose={() => setCreateModal(false)}
        title="Create New Carousel"
        primaryAction={{
          content: 'Create',
          onAction: handleCreate,
          disabled: !newCarousel.name.trim(),
        }}
        secondaryActions={[
          { content: 'Cancel', onAction: () => setCreateModal(false) },
        ]}
      >
        <Modal.Section>
          <FormLayout>
            <TextField
              label="Name"
              value={newCarousel.name}
              onChange={(value) => setNewCarousel({ ...newCarousel, name: value })}
              autoComplete="off"
              placeholder="e.g., Homepage Hero"
            />

            <Select
              label="Type"
              options={[
                { label: 'Custom (Pro)', value: 'custom' },
                { label: 'Standard', value: 'standard' },
              ]}
              value={newCarousel.type}
              onChange={(value) => setNewCarousel({ ...newCarousel, type: value })}
              helpText="Custom carousels let you manually select and order clips."
            />

            <Select
              label="Display Location"
              options={[
                { label: 'Homepage', value: 'homepage' },
                { label: 'Collection Pages', value: 'collection' },
                { label: 'Product Pages', value: 'product' },
                { label: 'All Pages', value: 'all' },
              ]}
              value={newCarousel.display_location}
              onChange={(value) => setNewCarousel({ ...newCarousel, display_location: value })}
            />
          </FormLayout>
        </Modal.Section>
      </Modal>
    </Page>
  );
}
