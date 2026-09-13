<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nieuwe periode</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="max-w-xl">
                    <form method="post" action="{{ route('admin.fixed-periods.store') }}">
                        @csrf
                        @include('admin.fixed-periods._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
