@extends('layouts.app')
@section('title', 'Leaderboard — SafeVoice')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/leaderboard.css') }}">
<link rel="stylesheet" href="{{ asset('css/theme.css') }}">
@endsection

@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard — SafeVoice</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/leaderboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive.css') }}">
      <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

    <!-- NAVBAR -->
    

    <div class="lb-layout">
        <div class="lb-container">

            <!-- HEADER -->
            <div class="lb-header">
                <i class="fas fa-trophy"></i>
                <h1>Leaderboard</h1>
                <p>Top SOS responders this month — Heroes of SafeVoice</p>
                <div class="lb-reset-badge">
                    <i class="fas fa-sync-alt"></i> Resets on June 1, 2026
                </div>
            </div>

            <!-- MY RANK BAR (logged in user) -->
            <div class="my-rank-bar">
                <div class="my-rank-left">
                    <div class="my-avatar"><i class="fas fa-user"></i></div>
                    <div>
                        <p class="my-rank-name">Shad Hossain <span class="you-tag">You</span></p>
                        <p class="my-rank-sub">12 responses this month</p>
                    </div>
                </div>
                <div class="my-rank-right">
                    <span class="my-rank-num">#7</span>
                    <span class="my-rank-label">Your Rank</span>
                </div>
            </div>

            <!-- TOP 3 PODIUM -->
            <div class="podium-section">
                <!-- 2nd -->
                <div class="podium-card silver">
                    <div class="podium-rank">2</div>
                    <div class="podium-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3>Shefa Akter</h3>
                    <p>38 responses</p>
                    <span class="podium-badge">🥈 Runner Up</span>
                    <div class="podium-bar" style="height: 80px; background: #C0C0C030; border-color: #C0C0C0;"></div>
                </div>

                <!-- 1st -->
                <div class="podium-card gold">
                    <div class="podium-crown"><i class="fas fa-crown"></i></div>
                    <div class="podium-rank">1</div>
                    <div class="podium-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3>Rakib Hassan</h3>
                    <p>42 responses</p>
                    <span class="podium-badge">🏆 Champion</span>
                    <div class="podium-bar" style="height: 110px; background: #FFD70030; border-color: #FFD700;"></div>
                </div>

                <!-- 3rd -->
                <div class="podium-card bronze">
                    <div class="podium-rank">3</div>
                    <div class="podium-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3>Nadia Islam</h3>
                    <p>31 responses</p>
                    <span class="podium-badge">🥉 Third Place</span>
                    <div class="podium-bar" style="height: 60px; background: #CD7F3230; border-color: #CD7F32;"></div>
                </div>
            </div>

            <!-- REWARDS SECTION -->
            <div class="rewards-section">
                <h3><i class="fas fa-gift"></i> Monthly Rewards</h3>
                <div class="rewards-grid">
                    <div class="reward-card">
                        <span class="reward-rank gold-text">🏆 1st Place</span>
                        <p>৳ 5,000 Cash + Champion Crest</p>
                    </div>
                    <div class="reward-card">
                        <span class="reward-rank silver-text">🥈 2nd Place</span>
                        <p>৳ 3,000 Cash + Silver Badge</p>
                    </div>
                    <div class="reward-card">
                        <span class="reward-rank bronze-text">🥉 3rd Place</span>
                        <p>৳ 1,500 Cash + Bronze Badge</p>
                    </div>
                </div>
            </div>

            <!-- FULL LEADERBOARD TABLE -->
            <div class="lb-table-section">
                <h3><i class="fas fa-list-ol"></i> Full Rankings</h3>
                <div class="lb-table">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Responder</th>
                                <th>Responses</th>
                                <th>Streak</th>
                                <th>Badge</th>
                            </tr>
                        </thead>
                        <tbody id="leaderboardBody">
                            <!-- injected by JS -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/leaderboard.js') }}"></script>
<script src="{{ asset('js/theme.js') }}"></script>
</body>
</html>
@endsection

@section('scripts')
<script src="{{ asset('js/leaderboard.js') }}"></script>
<script src="{{ asset('js/theme.js') }}"></script>
@endsection
