<nav class="nav">
    <div class="nav-inner">
        <a href="{{ route('dashboard') }}" class="nav-brand">{{ config('app.name', 'Laravel') }}</a>

        <div class="nav-links">
            <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Dashboard</a>
            <a href="{{ route('admin.tax-years.index') }}" @class(['active' => request()->routeIs('admin.tax-years.*')])>Belastingjaren</a>
            <a href="{{ route('admin.lenders.index') }}" @class(['active' => request()->routeIs('admin.lenders.*')])>Verstrekkers</a>
            <a href="{{ route('profile.edit') }}" @class(['active' => request()->routeIs('profile.edit')])>Profiel</a>
        </div>

        <div class="nav-user">
            <span>{{ Auth::user()->email }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Uitloggen</button>
            </form>
        </div>
    </div>
</nav>
