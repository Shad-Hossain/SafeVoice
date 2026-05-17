@extends('layouts.app')
@section('title', 'Complaint Track — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/track.css') }}">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
@endsection

@section('content')
<!-- NAVBAR -->
    

    <!-- MAIN -->
    <div class="track-layout">
        <div class="track-container">

            <!-- HEADER -->
            <div class="track-header">
                <i class="fas fa-search"></i>
                <h1>Track Your Complaint</h1>
                <p>Enter your Complaint ID or Anonymous Token to check status</p>
            </div>

            <!-- SEARCH CARD -->
            <div class="search-card">
                <div class="search-tabs">
                    <button class="tab-btn active" onclick="switchTab('id')">
                        <i class="fas fa-hashtag"></i> Complaint ID
                    </button>
                    <button class="tab-btn" onclick="switchTab('token')">
                        <i class="fas fa-user-secret"></i> Anonymous Token
                    </button>
                </div>

                <!-- Tab: Complaint ID -->
                <div class="tab-content active" id="tab-id">
                    <div class="search-input-group">
                        <input type="text" id="complaintIdInput" placeholder="e.g. SV-2026-4231" />
                        <button class="btn-search" onclick="trackComplaint()">
                            <i class="fas fa-search"></i> Track
                        </button>
                    </div>
                </div>

                <!-- Tab: Anonymous Token -->
                <div class="tab-content" id="tab-token">
                    <div class="search-input-group">
                        <input type="text" id="tokenInput" placeholder="Enter your secret token" />
                        <button class="btn-search" onclick="trackComplaint()">
                            <i class="fas fa-search"></i> Track
                        </button>
                    </div>
                </div>

                <!-- Error message -->
                <p class="error-msg" id="errorMsg">
                    <i class="fas fa-exclamation-circle"></i>
                    No complaint found. Please check your ID or token.
                </p>
            </div>

            <!-- RESULT CARD -->
            <div class="result-card" id="resultCard">

                <!-- Top Row -->
                <div class="result-top">
                    <div>
                        <div class="result-id" id="rId">—</div>
                        <div class="result-type" id="rType">—</div>
                    </div>
                    <span class="status review" id="rStatus">Under Review</span>
                </div>

                <!-- Info Grid -->
                <div class="result-info-grid">
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-calendar-alt"></i> Submitted</span>
                        <span class="info-value" id="rDate">—</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-map-marker-alt"></i> Location</span>
                        <span class="info-value" id="rLocation">—</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-user-secret"></i> Anonymous</span>
                        <span class="info-value" id="rAnon">—</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label"><i class="fas fa-user-tie"></i> Officer</span>
                        <span class="info-value" id="rOfficer">—</span>
                    </div>
                </div>

                <!-- Progress Tracker -->
                <div class="progress-tracker">
                    <h4>Case Progress</h4>
                    <div class="tracker-steps" id="trackerSteps">
                        <!-- Injected by JS -->
                    </div>
                </div>

                <!-- Admin Message -->
                <div class="admin-message" id="adminMessage">
                    <div class="admin-msg-header">
                        <i class="fas fa-comment-dots"></i>
                        <span>Message from Officer</span>
                    </div>
                    <p id="adminMsgText">—</p>
                </div>

            </div>
        </div>
    </div>

   <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/track.js') }}"></script>
    <script src="{{ asset('js/theme.js') }}"></script>
@endsection

@section('scripts')
@endsection