<div class="document-upload-container">
    <div class="upload-section">
        <h3 class="section-title">Upload Documents</h3>

        <form wire:submit="uploadDocument" class="upload-form">
            <div class="file-input-wrapper">
                <input
                    type="file"
                    wire:model="file"
                    accept=".txt,.pdf,.doc,.docx,.md"
                    class="file-input"
                    {{ $isUploading ? 'disabled' : '' }}
                    id="file-input" />
                <label for="file-input" class="file-label">
                    <span class="upload-icon">📁</span>
                    <span class="upload-text">
                        @if ($file)
                        {{ $file->getClientOriginalName() }}
                        @else
                        Select a file to upload
                        @endif
                    </span>
                </label>
            </div>

            <div class="button-group">
                <button
                    type="submit"
                    class="upload-button"
                    {{ $isUploading || !$file ? 'disabled' : '' }}>
                    @if ($isUploading)
                    <span class="loading-spinner"></span> Uploading...
                    @else
                    Upload
                    @endif
                </button>
            </div>

            @if ($uploadStatus)
            <div class="status-message {{ str_contains($uploadStatus, 'Error') ? 'error' : 'success' }}">
                {{ $uploadStatus }}
            </div>
            @endif
        </form>
    </div>

    @if (count($uploadedDocuments) > 0)
    <div class="documents-section">
        <h3 class="section-title">Recent Documents</h3>

        <div class="documents-list">
            @foreach ($uploadedDocuments as $doc)
            <div class="document-card" wire:key="doc-{{ $doc['id'] }}">
                <div class="document-icon">📄</div>

                <div class="document-info">
                    <p class="document-name">{{ $doc['filename'] }}</p>
                    <p class="document-date">{{ $doc['created_at'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif


    <style>
        .document-upload-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }

        .upload-section,
        .documents-section {
            background-color: white;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #e0e0e0;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 12px 0;
            color: #333;
        }

        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-input {
            display: none;
        }

        .file-label {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background-color: #f0f7ff;
            border: 2px dashed #007bff;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .file-label:hover {
            background-color: #e7f3ff;
            border-color: #0056b3;
        }

        .upload-icon {
            font-size: 1.5rem;
        }

        .upload-text {
            color: #333;
            font-size: 0.95rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        .upload-button {
            padding: 10px 16px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
            justify-content: center;
        }

        .upload-button:hover:not(:disabled) {
            background-color: #0056b3;
        }

        .upload-button:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }

        .loading-spinner {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .status-message {
            padding: 12px 16px;
            border-radius: 4px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .documents-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .document-card {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .document-card:hover {
            background-color: #f0f0f0;
        }

        .document-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .document-info {
            flex: 1;
            min-width: 0;
        }

        .document-name {
            font-weight: 600;
            color: #333;
            margin: 0;
            font-size: 0.9rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .document-date {
            font-size: 0.8rem;
            color: #999;
            margin: 2px 0 0 0;
        }
    </style>
</div>