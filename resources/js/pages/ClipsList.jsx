import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Page,
  Layout,
  Card,
  ResourceList,
  ResourceItem,
  Thumbnail,
  Text,
  Badge,
  Button,
  InlineStack,
  Banner,
  EmptyState,
  Modal,
  Pagination,
} from '@shopify/polaris';
import { PlusIcon, DeleteIcon } from '@shopify/polaris-icons';
import { api } from '../utils/api';

export default function ClipsList({ shop }) {
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [clips, setClips] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [error, setError] = useState(null);
  const [deleteModal, setDeleteModal] = useState(null);

  useEffect(() => {
    loadClips();
  }, []);

  async function loadClips(page = 1) {
    try {
      setLoading(true);
      const response = await api.getClips(page);
      setClips(response.data || []);
      setPagination({
        currentPage: response.current_page,
        lastPage: response.last_page,
        hasNext: response.current_page < response.last_page,
        hasPrevious: response.current_page > 1,
      });
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }

  async function handleDelete(clipId) {
    try {
      await api.deleteClip(clipId);
      setClips(clips.filter(c => c.id !== clipId));
      setDeleteModal(null);
    } catch (err) {
      setError(err.message);
    }
  }

  async function handlePublish(clipId, isPublished) {
    try {
      if (isPublished) {
        await api.unpublishClip(clipId);
      } else {
        await api.publishClip(clipId);
      }
      loadClips(pagination?.currentPage || 1);
    } catch (err) {
      setError(err.message);
    }
  }

  function renderItem(clip) {
    const { id, title, thumbnail_url, views_count, status, is_published, formatted_duration } = clip;

    return (
      <ResourceItem
        id={id}
        media={
          <Thumbnail
            source={thumbnail_url || 'https://via.placeholder.com/60x80'}
            alt={title}
            size="large"
          />
        }
        onClick={() => navigate(`/clips/${id}`)}
        shortcutActions={[
          {
            content: is_published ? 'Unpublish' : 'Publish',
            onAction: () => handlePublish(id, is_published),
            disabled: status !== 'ready',
          },
          {
            content: 'Delete',
            destructive: true,
            onAction: () => setDeleteModal(clip),
          },
        ]}
      >
        <InlineStack align="space-between">
          <div>
            <Text variant="bodyMd" fontWeight="bold" as="h3">{title}</Text>
            <Text variant="bodySm" tone="subdued">
              {views_count} views • {formatted_duration}
            </Text>
          </div>
          <InlineStack gap="200">
            <Badge tone={status === 'ready' ? 'success' : status === 'processing' ? 'attention' : 'critical'}>
              {status}
            </Badge>
            {status === 'ready' && (
              <Badge tone={is_published ? 'success' : undefined}>
                {is_published ? 'Published' : 'Draft'}
              </Badge>
            )}
          </InlineStack>
        </InlineStack>
      </ResourceItem>
    );
  }

  const emptyState = (
    <EmptyState
      heading="Create your first video clip"
      action={{
        content: 'Create Clip',
        onAction: () => navigate('/clips/new'),
      }}
      image="https://cdn.shopify.com/s/files/1/0262/4071/2726/files/emptystate-files.png"
    >
      <p>Upload short videos and tag products to create shoppable clips.</p>
    </EmptyState>
  );

  return (
    <Page
      title="Clips"
      primaryAction={{
        content: 'Create Clip',
        icon: PlusIcon,
        onAction: () => navigate('/clips/new'),
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
              items={clips}
              renderItem={renderItem}
              emptyState={emptyState}
            />
          </Card>

          {pagination && pagination.lastPage > 1 && (
            <div style={{ marginTop: '16px', display: 'flex', justifyContent: 'center' }}>
              <Pagination
                hasPrevious={pagination.hasPrevious}
                hasNext={pagination.hasNext}
                onPrevious={() => loadClips(pagination.currentPage - 1)}
                onNext={() => loadClips(pagination.currentPage + 1)}
              />
            </div>
          )}
        </Layout.Section>
      </Layout>

      <Modal
        open={deleteModal !== null}
        onClose={() => setDeleteModal(null)}
        title="Delete clip?"
        primaryAction={{
          content: 'Delete',
          destructive: true,
          onAction: () => handleDelete(deleteModal?.id),
        }}
        secondaryActions={[
          {
            content: 'Cancel',
            onAction: () => setDeleteModal(null),
          },
        ]}
      >
        <Modal.Section>
          <Text>
            Are you sure you want to delete "{deleteModal?.title}"? This action cannot be undone.
          </Text>
        </Modal.Section>
      </Modal>
    </Page>
  );
}
