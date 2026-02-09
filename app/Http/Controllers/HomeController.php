<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    private const RECENT_TABLES_COOKIE = 'pokie_recent_tables';

    public function index(Request $request): View
    {
        $recentTables = json_decode($request->cookie(self::RECENT_TABLES_COOKIE, '[]'), true) ?: [];

        return view('home', [
            'recentTables' => $recentTables,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // #region agent log
        $dbPath = config('database.connections.sqlite.database');
        $dir = dirname($dbPath);
        $fileExists = file_exists($dbPath);
        $dirExists = is_dir($dir);
        $fileWritable = $fileExists && is_writable($dbPath);
        $dirWritable = $dirExists && is_writable($dir);
        $filePerms = $fileExists ? substr(sprintf('%o', fileperms($dbPath)), -4) : null;
        $dirPerms = $dirExists ? substr(sprintf('%o', fileperms($dir)), -4) : null;
        $logPath = base_path('.cursor/debug.log');
        @file_put_contents($logPath, json_encode(['id' => 'db_check', 'timestamp' => (int) (microtime(true) * 1000), 'location' => __FILE__ . ':' . __LINE__, 'message' => 'SQLite path and permissions before create', 'data' => ['db_path' => $dbPath, 'dir' => $dir, 'file_exists' => $fileExists, 'dir_exists' => $dirExists, 'file_writable' => $fileWritable, 'dir_writable' => $dirWritable, 'file_perms' => $filePerms, 'dir_perms' => $dirPerms], 'hypothesisId' => 'H1-H5']) . "\n", FILE_APPEND | LOCK_EX);
        // #endregion

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $table = Table::create([
            'name' => $validated['name'],
            'token' => Str::random(32),
            'manager_token' => Str::random(32),
        ]);

        return redirect()->route('table.manager', [
            'token' => $table->token,
            'manager_token' => $table->manager_token,
        ]);
    }
}
