<x-guest-layout>
    <form method="POST" action="{{ route('login') }}">
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
                        <p><strong>IPアドレス:</strong> <span x-text="info.ip"></span></p>
                        <p><strong>User Agent:</strong> <span x-text="info.user_agent"></span></p>
                        <p><strong>ログイン制限ポリシー:</strong> 5回 / 分 (アカウント毎)</p>
                        <p class="text-gray-400 mt-1">※ ログイン試行回数はメールアドレスごとにカウントされるため、ここでは表示されません。</p>
                    </div>
                </template>
                <template x-if="!info">
                    <p>読み込み中...</p>
                </template>
            </div>
        </div>

        <!-- Email Address -->
        <div>
            <label for="email" class="block font-medium text-sm text-gray-700">メールアドレス</label>
            <input id="email"
                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            @error('email')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div class="mt-4">
            <label for="password" class="block font-medium text-sm text-gray-700">パスワード</label>
            <input id="password"
                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                type="password" name="password" required autocomplete="current-password" />
            @error('password')
                <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">ログイン状態を保持する</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                href="{{ route('register') }}">
                新規登録
            </a>

            <button type="submit"
                class="ms-3 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                ログイン
            </button>
        </div>
    </form>

    <!-- Alpine.js for interactivity -->
    <script src="//unpkg.com/alpinejs" defer></script>
</x-guest-layout>