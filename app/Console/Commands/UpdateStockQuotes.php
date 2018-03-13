<?php

namespace App\Console\Commands;

use App\Domain\Stock;
use App\Infrastructure\Services\AlphaVantage;
use App\Infrastructure\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateStockQuotes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes:update 
                            {--batch-size=100 : The number of quotes to retrieve from the AlphaVantage API in each request (max 100)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get the latest stock price from the AlphaVantage API for each Stock in the database.';

    /**
     * @var AlphaVantage $client
     */
    protected $client;

    /**
     * Create a new command instance.
     *
     * @param AlphaVantage $alphaVantage
     */
    public function __construct(AlphaVantage $alphaVantage)
    {
        parent::__construct();
        $this->client = $alphaVantage;
    }

    /**
     * Execute the console command.
     *
     * @param StockService $stocks
     * @return mixed
     */
    public function handle(StockService $stocks)
    {
        Log::info('Running UpdateStockQuotes command...');
        $startTime = microtime(true);

        $quotes = collect([]);
        Stock::chunk($this->option('batch-size'), function($groupOfStocks) use (&$quotes) {
            $quotes = $quotes->concat($this->client->batchQuote($groupOfStocks->pluck('symbol')));
        });

        $quotes->each(function ($stockQuote) use (&$stocks) {
            $stocks->update($stockQuote->symbol, $stockQuote->toArray());
        });

        $duration = microtime(true) - $startTime;
        $quotesCount = $quotes->count();
        Log::info("UpdateStockQuotes command completed: $quotesCount quotes updated in $duration seconds.");

        return true;
    }
}
