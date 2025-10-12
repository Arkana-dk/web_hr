<?php


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportRoute;
use Illuminate\Http\Request;

class TransportRouteController extends Controller
{
    public function index(Request $request) {
        $q = $request->get('q');
        $routes = TransportRoute::query()
            ->when($q, fn($qr) => $qr->where('route_name','like',"%{$q}%"))
            ->orderBy('route_name')
            ->paginate(10);
        return view('admin.pages.transportroutes.index', compact('routes'));
        }


    public function create()
    {
        return view('admin.pages.transportroutes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'route_name' => 'required|string|max:255',
        ]);

        TransportRoute::create($request->only('route_name'));

        return redirect()->route('admin.transportroutes.index')->with('success', 'Rute ditambahkan!');
    }

        public function edit(TransportRoute $transportroute)
    {
        return view('admin.pages.transportroutes.edit', [
            'route' => $transportroute
        ]);
    }


    public function update(Request $request, TransportRoute $transportroute)
{
    $request->validate([
        'route_name' => 'required|string|max:255'
    ]);

    $transportroute->update([
        'route_name' => $request->route_name
    ]);

    return redirect()->route('admin.transportroutes.index')->with('success', 'Jurusan berhasil diperbarui.');
}


    public function destroy(TransportRoute $transportroute)
{
    $transportroute->delete();
    return redirect()->route('admin.transportroutes.index')
                     ->with('success', 'Jurusan berhasil dihapus.');
}

}

