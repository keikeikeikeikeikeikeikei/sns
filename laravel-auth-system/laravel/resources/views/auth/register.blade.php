<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Rate Limit Info Section -->
        <div class="mb-4" x-data="{ showInfo: false, info: null }">
            <button type="button" 
                    @click="showInfo = !showInfo; if(showInfo && !info) fetch('/rate-limit-status').then(r => r.json()).then(d => info = d)" 
                    class="text-xs text-indigo-600 hover:text-indigo-800 underline focus:outline-none">
                [接続情報を表示]
            </button>
            
            <div x-show="showInfo" class="mt-2 p-3 bg-gray-50 border border-gray-200 rounded text-xs text-gray-600">
                <template x-if="info">
                    <div>
                        <p><strong>あなたのIP:</strong> <span x-text="info.ip"></span></p>
                        <p><strong>User Agent:</strong> <span x-text="info.user_agent"></span></p>
                        <p><strong>制限ポリシー:</strong> <span x-text="info.policy"></span></p>
                        <p><strong>推定残り回数:</strong> <span x-text="info.remaining_estimate"></span> / 3</p>
                        <p><strong>デバイスID (Fingerprint):</strong> <span id="fingerprint-display">取得中...</span></p>
                    </div>
                </template>
                <template x-if="!info">
                    <p>読み込み中...</p>
                </template>
            </div>
        </div>

        <!-- Honeypot Field -->
        <div style="display: none;">
            <label for="website_url">Website</label>
            <input type="text" name="website_url" id="website_url" value="">
        </div>

        <!-- Name -->
        <div>
            <label for="name" class="block font-medium text-sm text-gray-700">名前</label>
            <input id="name" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            @error('name')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <label for="email" class="block font-medium text-sm text-gray-700">メールアドレス</label>
            <input id="email" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" type="email" name="email" :value="old('email')" required autocomplete="username" />
            @error('email')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div class="mt-4">
            <label for="password" class="block font-medium text-sm text-gray-700">パスワード</label>
            <input id="password" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" type="password" name="password" required autocomplete="new-password" />
            @error('password')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <label for="password_confirmation" class="block font-medium text-sm text-gray-700">パスワード確認</label>
            <input id="password_confirmation" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" type="password" name="password_confirmation" required autocomplete="new-password" />
            @error('password_confirmation')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                既に登録済みですか？
            </a>

            <button type="submit" class="ms-4 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                登録
            </button>
        </div>
    </form>
    
    <!-- Alpine.js for interactivity -->
    <script src="//unpkg.com/alpinejs" defer></script>
</x-guest-layout>
