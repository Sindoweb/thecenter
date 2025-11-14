<div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
    <a
        href="{{ route('home', ['locale' => 'en']) }}"
        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md transition-all {{ app()->getLocale() === 'en' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
    >
        EN
    </a>
    <a
        href="{{ route('home', ['locale' => 'nl']) }}"
        class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-md transition-all {{ app()->getLocale() === 'nl' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600 hover:text-gray-900' }}"
    >
        NL
    </a>
</div>
