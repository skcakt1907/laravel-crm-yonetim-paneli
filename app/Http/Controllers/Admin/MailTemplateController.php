<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MailTemplateController extends Controller
{
    public function index()
    {
        $templates = DB::table('mail_templates')->orderBy('name')->get();
        return view('admin.mail-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.mail-templates.form', ['template' => null]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['aktif'] = $request->has('aktif') ? 1 : 0;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::table('mail_templates')->insert($data);
        return redirect()->route('admin.mail-templates.index')->with('success', 'Şablon eklendi.');
    }

    public function edit(int $id)
    {
        $template = DB::table('mail_templates')->where('id', $id)->first();
        abort_if(!$template, 404);
        return view('admin.mail-templates.form', compact('template'));
    }

    public function update(Request $request, int $id)
    {
        $data = $this->validateData($request);
        $data['aktif'] = $request->has('aktif') ? 1 : 0;
        $data['updated_at'] = now();

        DB::table('mail_templates')->where('id', $id)->update($data);
        return redirect()->route('admin.mail-templates.index')->with('success', 'Şablon güncellendi.');
    }

    public function destroy(int $id)
    {
        DB::table('mail_templates')->where('id', $id)->delete();
        return back()->with('success', 'Şablon silindi.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'    => 'required|string|max:150',
            'subject' => 'required|string|max:200',
            'body'    => 'required|string',
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'sablon';
        $slug = $base; $i = 2;
        while (DB::table('mail_templates')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
