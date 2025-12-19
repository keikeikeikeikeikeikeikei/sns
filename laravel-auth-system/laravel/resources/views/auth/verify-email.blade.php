<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        これは安全なエリアです。続ける前に、メールアドレスに送信されたリンクをクリックして、メールアドレスを確認してください。メールが届いていない場合は、再送信できます。
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            新しい確認リンクが、登録時に指定されたメールアドレスに送信されました。
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                確認メールを再送信
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                ログアウト
            </button>
        </form>
    </div>
</x-guest-layout>
