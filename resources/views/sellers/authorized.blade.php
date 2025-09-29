<div class="container">
    <div class="card">
        <div class="header">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                <path d="M12 2L13.09 7.09L18 8L13.09 8.91L12 14L10.91 8.91L6 8L10.91 7.09L12 2Z" fill="#FF0050"/>
                <path d="M19 15L19.5 17.5L22 18L19.5 18.5L19 21L18.5 18.5L16 18L18.5 17.5L19 15Z" fill="#25F4EE"/>
            </svg>
            <h1>{{ __('TikTok Shop Authorized!') }}</h1>
            <p>{{ __('Access token has been generated. You may now proceed to call any supported TikTok API using this SDK.') }}</p>
        </div>

        @if($accessTokens && $accessTokens->isNotEmpty())
            <div class="section">
                <div class="item">
                    <span class="label">{{ __('Authorization Code') }}</span>
                    <code>{{ $code }}</code>
                </div>
            </div>

            @foreach($accessTokens as $accessToken)
                @if($accessToken->subjectable && $accessToken->subjectable instanceof \Laraditz\TikTok\Models\TiktokShop)
                    <div class="section">
                        <h2>{{ __('Shop Information') }}</h2>
                        <div class="item">
                            <span class="label">{{ __('Shop ID') }}</span>
                            <span>{{ $accessToken->subjectable->id ?? '-' }}</span>
                        </div>
                        <div class="item">
                            <span class="label">{{ __('Shop Name') }}</span>
                            <span>{{ $accessToken->subjectable->name ?? '-' }}</span>
                        </div>
                        <div class="item">
                            <span class="label">{{ __('Shop Code') }}</span>
                            <span>{{ $accessToken->subjectable->code ?? '-' }}</span>
                        </div>
                    </div>
                @endif

                <div class="section">
                    <h2>{{ __('Token Information') }}</h2>
                    <div class="item">
                        <span class="label">{{ __('Access Token') }}</span>
                        <code>{{ $accessToken->access_token }}</code>
                    </div>
                    <div class="item">
                        <span class="label">{{ __('Access Token Expires') }}</span>
                        <span class="date">{{ $accessToken->expires_at?->toDateTimeString() }}</span>
                    </div>
                    <div class="item">
                        <span class="label">{{ __('Refresh Token') }}</span>
                        <code>{{ $accessToken->refresh_token }}</code>
                    </div>
                    <div class="item">
                        <span class="label">{{ __('Refresh Token Expires') }}</span>
                        <span class="date">{{ $accessToken->refresh_expires_at?->toDateTimeString() }}</span>
                    </div>
                </div>
            @endforeach
        @endif

        <div class="section" style="text-align: center;">
            <a href="{{ config('tiktok.home_url') }}" class="home-btn">← Back to Home</a>
        </div>
    </div>
</div>
    
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #000;
            color: #fff;
        }

        .container {
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: #161823;
            border-radius: 12px;
            width: 100%;
            max-width: 700px;
            border: 1px solid #25252D;
        }

        .header {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #FF0050, #25F4EE);
            border-radius: 12px 12px 0 0;
            color: #fff;
        }

        .header svg {
            margin-bottom: 12px;
        }

        .header h1 {
            margin: 0 0 8px 0;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .section {
            padding: 16px 20px;
            border-bottom: 1px solid #25252D;
        }

        .section:last-child {
            border-bottom: none;
        }

        h2 {
            margin: 0 0 12px 0;
            font-size: 1rem;
            font-weight: 600;
            color: #FF0050;
        }

        .item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #25252D;
            gap: 16px;
        }

        .item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .item:first-child {
            padding-top: 0;
        }

        .label {
            font-weight: 600;
            color: #25F4EE;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        code {
            background: #25252D;
            color: #25F4EE;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
            font-size: 0.8rem;
            word-break: break-all;
            max-width: 300px;
            overflow-wrap: break-word;
        }

        .date {
            font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
            font-size: 0.8rem;
            color: #fff;
        }

        .home-btn {
            display: inline-block;
            background: #FF0050;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: opacity 0.2s;
        }

        .home-btn:hover {
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header {
                padding: 16px;
            }

            .header h1 {
                font-size: 1.25rem;
            }

            .header p {
                font-size: 0.9rem;
            }

            .section {
                padding: 12px 16px;
            }

            .item {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }

            code {
                max-width: 100%;
                font-size: 0.75rem;
            }
        }
    </style>