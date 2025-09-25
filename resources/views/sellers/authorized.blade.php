<div class="container">
    <div class="box">
        <h2>{{ __('TikTok Seller Authorized!') }}</h2>
        @if($accessTokens && $accessTokens->isNotEmpty())
        <p>{{ __('Access token has been generated. You may now proceed to call any supported TikTok API using this SDK.') }}</p>
        <p><strong>{{ __('Authorization code') }}</strong>: {{ $code }}</p>

        @foreach($accessTokens as $accessToken)
        <ul>  
            @if($accessToken->subjectable && $accessToken->subjectable instanceof \Laraditz\TikTok\Models\TiktokShop)
            <li><strong>{{ __('Shop ID') }}</strong>: {{ $accessToken->subjectable->identifier ?? '-' }}</li>
            <li><strong>{{ __('Shop name') }}</strong>: {{ $accessToken->subjectable->name  ?? '-' }}</li>
            <li><strong>{{ __('Shop code') }}</strong>: {{ $accessToken->subjectable->code ?? '-' }}</li>
            @endif
            <li><strong>{{ __('Access token') }}</strong>: {{ $accessToken->access_token }} </li>
            <li><strong>{{ __('Access token expires at') }}</strong>: {{ $accessToken->expires_at?->toDateTimeString() }} </li>
            <li><strong>{{ __('Refresh Token') }}</strong>: {{ $accessToken->refresh_token }}</li>
            <li><strong>{{ __('Refresh token expires at') }}</strong>: {{ $accessToken->refresh_expires_at?->toDateTimeString() }} </li>
        </ul>
        @endforeach
        @endif
       
    
    </div>
    </div>
    
    <style>
    .container{
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px;
        padding-top: 20px;
        font-family: "Trebuchet MS", Helvetica, Verdana, sans-serif;
    }
    .box {
        border: #cccccc 1px solid;
        padding: 30px 20px;
        border-radius: 15px;
        width: 700px;
        max-width: 100%;
    }
    
    h2 {
        margin: 0;
        margin-bottom: 10px;
    }
    
    ul{
        margin: 0;
        padding: 0;
    }
    
    ul > li {
        list-style-type: none;
        line-height: 1.5;
        word-break: break-all;
    }
    </style>