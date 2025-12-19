<x-guest-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-4">
                    <h2 class="text-xl font-bold">ダッシュボード (ログイン中)</h2>

                    <div class="border p-4 rounded bg-gray-50">
                        <h3 class="font-semibold mb-2">ユーザー情報</h3>
                        <ul class="list-disc pl-5">
                            <li><strong>ID:</strong> {{ auth()->id() }}</li>
                            <li><strong>名前:</strong> {{ auth()->user()->name }}</li>
                            <li><strong>メール:</strong> {{ auth()->user()->email }}</li>
                        </ul>
                    </div>

                    <div class="border p-4 rounded bg-gray-50">
                        <h3 class="font-semibold mb-2">セッション情報</h3>
                        <ul class="list-disc pl-5">
                            <li><strong>セッションID:</strong> {{ session()->getId() }}</li>
                            <li><strong>IPアドレス:</strong> {{ request()->ip() }}</li>
                            <li><strong>User Agent:</strong> {{ request()->userAgent() }}</li>
                        </ul>
                    </div>



                    <form method="POST" action="{{ route('logout') }}" class="mt-6">
                        @csrf
                        <button type="submit"
                            class="underline text-sm text-red-600 hover:text-red-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            ログアウト
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>