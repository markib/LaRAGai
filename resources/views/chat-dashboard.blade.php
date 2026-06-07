@extends('layouts.main')

@section('content')
<div class="chat-dashboard-wrapper">
    <!-- Sidebar with Conversation List -->
    <aside class="sidebar">
        <livewire:conversation-list />
    </aside>

    <!-- Main Chat Area -->
    <main class="main-content">
        <div class="chat-and-upload">
            <!-- Chat Component -->
            <div class="chat-section">
                <livewire:chat />
            </div>

            <!-- Document Upload Component -->
            <aside class="upload-sidebar">
                <livewire:document-upload />
            </aside>
        </div>
    </main>
</div>

<style>
    .chat-dashboard-wrapper {
        display: flex;
        height: 100vh;
        background-color: #fff;
        overflow: hidden;
    }

        .sidebar {
            width: 250px;
            height: 100%;
            background-color: #f8f9fa;
            border-right: 1px solid #e0e0e0;
            overflow-y: auto;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        .chat-and-upload {
            display: flex;
            flex: 1;
            gap: 0;
            height: 100%;
            overflow: hidden;
        }

        .chat-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .upload-sidebar {
            width: 300px;
            background-color: #f9f9f9;
            border-left: 1px solid #e0e0e0;
            overflow-y: auto;
            padding: 12px;
        }

        /* Responsive design for smaller screens */
        @media (max-width: 1024px) {
            .upload-sidebar {
                display: none;
            }

            .chat-and-upload {
                gap: 0;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .upload-sidebar {
                width: 250px;
            }
        }

        @media (max-width: 640px) {
            .chat-dashboard-wrapper {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: 150px;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }

            .upload-sidebar {
                display: none;
            }
        }

        /* Scrollbar styling */
        .sidebar::-webkit-scrollbar,
        .upload-sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track,
        .upload-sidebar::-webkit-scrollbar-track {
            background-color: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb,
        .upload-sidebar::-webkit-scrollbar-thumb {
            background-color: #d0d0d0;
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover,
        .upload-sidebar::-webkit-scrollbar-thumb:hover {
            background-color: #999;
        }
    </style>
@endsection
