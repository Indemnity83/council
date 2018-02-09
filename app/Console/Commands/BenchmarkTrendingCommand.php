<?php

namespace App\Console\Commands;

use App\Thread;
use App\Trending;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BenchmarkTrendingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'benchmark:trending {--t|threads= : Number of threads to generate} {--i|impressions= : Number of thread impressions to execute}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run a benchmark on trending implementation.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $threadCount = $this->option('threads') ?: 1000;
        $impressions = $this->option('impressions') ?: 20000;

        // Reset all migrations
        $this->call('migrate:fresh');

        // Populate the DB with test data
        $this->comment('Mocking threads');
        $bar = $this->output->createProgressBar($threadCount);
        for($i = 0; $i < $threadCount; ++$i) {
            factory(Thread::class)->create();
            $bar->advance();
        }
        $bar->finish();

        // Dump out a list of all thread paths
        $this->comment('Sequencing requests');
        Storage::delete('list.txt');
        $bar = $this->output->createProgressBar($threadCount);
        $paths = Thread::all()->map->path();
        $paths->each(function ($path) use (&$bar) {
            Storage::append('list.txt', $path);
            $bar->advance();
        });
        $bar->finish();

        // Clear any existing trending data.
        $this->comment('Flushing trends');
        $trending = new Trending();
        $trending->reset();

        $this->info('Prep complete, starting benchmark...');

        // Perform benchmark
        $start = $this->starttime();
        for($i = 0; $i < $impressions; ++$i) {
            //
        }
        $this->line($this->endtime($start));
    }

    private function starttime() {
        $r = explode( ' ', microtime() );
        $r = $r[1] + $r[0];
        return $r;
    }

    private function endtime($starttime) {
        $r = explode( ' ', microtime() );
        $r = $r[1] + $r[0];
        $r = round($r - $starttime,4);
        return '<comment>Execution Time</comment>: '.$r.' seconds';
    }
}
