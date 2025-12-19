<x-guest-layout>
    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-xl font-bold mb-4">ユーザーインターフェース設定</h2>

                    @if (session('status') === 'settings-updated')
                        <div class="mb-4 font-medium text-sm text-green-600">
                            設定を保存しました。
                        </div>
                    @endif

                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf

                        <!-- Theme -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700 mb-1">テーマ</label>
                            <div class="flex gap-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="theme" value="light" class="text-indigo-600 focus:ring-indigo-500" {{ ($settings['theme'] ?? 'light') === 'light' ? 'checked' : '' }}>
                                    <span class="ml-2">ライト (標準)</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="theme" value="dark" class="text-indigo-600 focus:ring-indigo-500" {{ ($settings['theme'] ?? '') === 'dark' ? 'checked' : '' }}>
                                    <span class="ml-2">ダーク</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="theme" value="blue" class="text-indigo-600 focus:ring-indigo-500" {{ ($settings['theme'] ?? '') === 'blue' ? 'checked' : '' }}>
                                    <span class="ml-2">ブルー</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="theme" value="contrast" class="text-indigo-600 focus:ring-indigo-500" {{ ($settings['theme'] ?? '') === 'contrast' ? 'checked' : '' }}>
                                    <span class="ml-2">ハイコントラスト</span>
                                </label>
                            </div>
                        </div>

                        <!-- Font Size -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700 mb-1">文字サイズ</label>
                            <select name="font_size" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full">
                                <option value="small" {{ ($settings['font_size'] ?? 'medium') === 'small' ? 'selected' : '' }}>小</option>
                                <option value="medium" {{ ($settings['font_size'] ?? 'medium') === 'medium' ? 'selected' : '' }}>中 (標準)</option>
                                <option value="large" {{ ($settings['font_size'] ?? 'medium') === 'large' ? 'selected' : '' }}>大</option>
                            </select>
                        </div>

                        <!-- Layout Density -->
                        <div class="mb-4">
                            <label class="block font-medium text-sm text-gray-700 mb-1">表示密度</label>
                            <select name="layout_density" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm w-full">
                                <option value="compact" {{ ($settings['layout_density'] ?? 'comfortable') === 'compact' ? 'selected' : '' }}>コンパクト</option>
                                <option value="comfortable" {{ ($settings['layout_density'] ?? 'comfortable') === 'comfortable' ? 'selected' : '' }}>通常</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('dashboard') }}" class="underline text-sm text-gray-600 hover:text-gray-900 mr-4">
                                ダッシュボードへ戻る
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                保存
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
