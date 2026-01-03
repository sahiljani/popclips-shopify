import React, { useState, useCallback, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Page,
  Layout,
  Card,
  FormLayout,
  TextField,
  DropZone,
  Text,
  Thumbnail,
  Banner,
  Button,
  BlockStack,
  InlineStack,
  ProgressBar,
  ButtonGroup,
  Spinner,
} from '@shopify/polaris';
import { api } from '../utils/api';

export default function ClipCreate({ shop }) {
  const navigate = useNavigate();
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [file, setFile] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [error, setError] = useState(null);
  const [mode, setMode] = useState('upload');
  const [libraryFiles, setLibraryFiles] = useState([]);
  const [libraryLoading, setLibraryLoading] = useState(false);
  const [selectedFile, setSelectedFile] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [pageInfo, setPageInfo] = useState(null);

  const handleDropZoneDrop = useCallback((_dropFiles, acceptedFiles) => {
    if (acceptedFiles.length > 0) {
      setFile(acceptedFiles[0]);
      setSelectedFile(null);
    }
  }, []);

  const validVideoTypes = ['video/mp4', 'video/webm', 'video/quicktime'];

  async function pollForVideoReady(fileId, maxAttempts = 30, interval = 10000) {
    for (let i = 0; i < maxAttempts; i++) {
      await new Promise((resolve) => setTimeout(resolve, interval));

      const fileStatus = await api.getShopifyFileStatus(fileId);

      if (fileStatus.status === 'ready' && fileStatus.url) {
        return fileStatus;
      }

      if (fileStatus.file_status === 'FAILED') {
        throw new Error('Shopify video processing failed. Please try again.');
      }
    }

    throw new Error('Video is still processing. Please check back in a few minutes.');
  }

  const fileUpload = !file && (
    <DropZone.FileUpload
      actionHint="Uploads directly to Shopify Files"
      actionTitle="Add video"
    />
  );

  const uploadedFile = file && (
    <BlockStack gap="200" align="center">
      <Thumbnail
        size="large"
        alt={file.name}
        source={URL.createObjectURL(file)}
      />
      <Text variant="bodySm" as="p">{file.name}</Text>
      <Text variant="bodySm" tone="subdued">
        {(file.size / 1024 / 1024).toFixed(2)} MB
      </Text>
      <Button variant="plain" onClick={() => setFile(null)}>Remove</Button>
    </BlockStack>
  );

  useEffect(() => {
    if (mode === 'library') {
      void fetchFiles();
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [mode]);

  async function fetchFiles(afterCursor = null, query = searchTerm) {
    try {
      setLibraryLoading(true);
      const response = await api.listShopifyFiles(query, afterCursor);

      setLibraryFiles((prev) => {
        if (afterCursor) {
          return [...prev, ...response.data];
        }
        return response.data;
      });

      setPageInfo(response.page_info);
    } catch (err) {
      setError(err.message);
    } finally {
      setLibraryLoading(false);
    }
  }

  async function uploadToShopifyFiles(selected) {
    setProgress(10);

    const target = await api.createShopifyStagedUpload({
      file_name: selected.name,
      mime_type: selected.type,
      file_size: selected.size,
    });

    if (!target?.url || !target?.parameters) {
      throw new Error('Could not get upload target from Shopify.');
    }

    const formData = new FormData();
    target.parameters.forEach(({ name, value }) => {
      formData.append(name, value);
    });
    formData.append('file', selected);

    const uploadResponse = await fetch(target.url, {
      method: 'POST',
      body: formData,
    });

    if (!uploadResponse.ok) {
      throw new Error('Upload to Shopify Files failed.');
    }

    setProgress(65);

    const completed = await api.completeShopifyUpload({
      resource_url: target.resource_url,
      file_name: selected.name,
    });

    if (!completed?.id) {
      throw new Error('Failed to finalize Shopify file upload.');
    }

    // Shopify processes videos asynchronously
    // If status is 'processing', poll until ready
    if (completed.status === 'processing') {
      setProgress(70);
      const ready = await pollForVideoReady(completed.id);
      setProgress(80);

      return {
        ...ready,
        file_size: selected.size,
        original_filename: selected.name,
      };
    }

    return {
      ...completed,
      file_size: selected.size,
      original_filename: selected.name,
    };
  }

  async function handleSubmit() {
    if (!title.trim()) {
      setError('Please enter a title');
      return;
    }

    if (mode === 'upload' && !file) {
      setError('Please upload a video');
      return;
    }

    if (mode === 'library' && !selectedFile) {
      setError('Please select a video from Shopify Files');
      return;
    }

    try {
      setUploading(true);
      setProgress(5);
      setError(null);

      const fileData = mode === 'upload'
        ? await uploadToShopifyFiles(file)
        : selectedFile;

      setProgress(85);

      const clip = await api.createClip({
        title: title.trim(),
        description: description.trim(),
        video_url: fileData.url,
        thumbnail_url: fileData.preview_image_url || fileData.thumbnail_url || null,
        shopify_file_id: fileData.id,
        duration: fileData.duration,
        file_size: fileData.file_size,
        original_filename: fileData.original_filename,
      });

      setProgress(100);
      navigate(`/clips/${clip.id}`);
    } catch (err) {
      setError(err.message);
    } finally {
      setUploading(false);
    }
  }

  const canSubmit = title.trim() && ((mode === 'upload' && file) || (mode === 'library' && selectedFile));

  return (
    <Page
      title="Create New Clip"
      backAction={{ content: 'Clips', onAction: () => navigate('/clips') }}
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
          <InlineStack align="space-between" blockAlign="center" gap="200">
            <Text variant="headingMd" as="h2">Video Source</Text>
            <ButtonGroup segmented>
              <Button
                pressed={mode === 'upload'}
                onClick={() => setMode('upload')}
                disabled={uploading}
              >
                Upload new
              </Button>
              <Button
                pressed={mode === 'library'}
                onClick={() => setMode('library')}
                disabled={uploading}
              >
                Select from Files
              </Button>
            </ButtonGroup>
          </InlineStack>
        </Layout.Section>

        {mode === 'upload' && (
          <Layout.Section>
            <Card>
              <FormLayout>
                <DropZone
                  accept={validVideoTypes.join(',')}
                  type="file"
                  onDrop={handleDropZoneDrop}
                  disabled={uploading}
                >
                  {uploadedFile}
                  {fileUpload}
                </DropZone>

                {uploading && (
                  <BlockStack gap="200">
                    <ProgressBar progress={progress} />
                    <Text variant="bodySm" tone="subdued">
                      {progress < 65 ? 'Uploading to Shopify...' : progress < 80 ? 'Processing video (this may take 2-3 minutes)...' : 'Finalizing...'}
                    </Text>
                  </BlockStack>
                )}
              </FormLayout>
            </Card>
          </Layout.Section>
        )}

        {mode === 'library' && (
          <Layout.Section>
            <Card>
              <FormLayout>
                <InlineStack gap="200" blockAlign="end">
                  <TextField
                    label="Search Shopify Files"
                    value={searchTerm}
                    onChange={setSearchTerm}
                    autoComplete="off"
                    placeholder="Filename or keyword"
                    disabled={libraryLoading || uploading}
                  />
                  <Button
                    onClick={() => fetchFiles(null, searchTerm)}
                    loading={libraryLoading}
                    disabled={uploading}
                  >
                    Search
                  </Button>
                </InlineStack>

                {libraryLoading && libraryFiles.length === 0 ? (
                  <InlineStack gap="200" align="center">
                    <Spinner accessibilityLabel="Loading files" />
                    <Text tone="subdued">Loading Shopify files...</Text>
                  </InlineStack>
                ) : (
                  <BlockStack gap="200">
                    {libraryFiles.map((item) => {
                      const isSelected = selectedFile?.id === item.id;
                      return (
                        <Card key={item.id}>
                          <InlineStack align="space-between" blockAlign="center" gap="400">
                            <InlineStack gap="300" blockAlign="center">
                              <Thumbnail
                                size="large"
                                alt={item.original_filename || 'Video file'}
                                source={item.thumbnail_url || 'https://cdn.shopify.com/s/files/1/0533/2089/files/placeholder-images-image_large.png'}
                              />
                              <BlockStack gap="100">
                                <Text variant="bodyMd" as="p">{item.original_filename || 'Untitled video'}</Text>
                                <Text variant="bodySm" tone="subdued">{item.mime_type || 'video'}</Text>
                              </BlockStack>
                            </InlineStack>
                            <Button
                              variant={isSelected ? 'primary' : 'secondary'}
                              onClick={() => setSelectedFile(item)}
                              disabled={uploading}
                            >
                              {isSelected ? 'Selected' : 'Select'}
                            </Button>
                          </InlineStack>
                        </Card>
                      );
                    })}

                    {pageInfo?.hasNextPage && (
                      <Button
                        onClick={() => fetchFiles(pageInfo.endCursor, searchTerm)}
                        loading={libraryLoading}
                        disabled={uploading}
                      >
                        Load more
                      </Button>
                    )}

                    {!libraryLoading && libraryFiles.length === 0 && (
                      <Text tone="subdued">No videos found in Shopify Files.</Text>
                    )}
                  </BlockStack>
                )}
              </FormLayout>
            </Card>
          </Layout.Section>
        )}

        <Layout.Section>
          <Card>
            <FormLayout>
              <TextField
                label="Title"
                value={title}
                onChange={setTitle}
                autoComplete="off"
                placeholder="Enter clip title"
                disabled={uploading}
              />

              <TextField
                label="Description"
                value={description}
                onChange={setDescription}
                multiline={3}
                autoComplete="off"
                placeholder="Enter clip description (optional)"
                disabled={uploading}
              />
            </FormLayout>
          </Card>
        </Layout.Section>

        <Layout.Section>
          <InlineStack gap="400" align="end">
            <Button onClick={() => navigate('/clips')} disabled={uploading}>
              Cancel
            </Button>
            <Button
              variant="primary"
              onClick={handleSubmit}
              loading={uploading}
              disabled={!canSubmit || uploading}
            >
              {uploading ? 'Saving...' : 'Save Clip'}
            </Button>
          </InlineStack>
        </Layout.Section>
      </Layout>
    </Page>
  );
}
