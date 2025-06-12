<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoCategory;
use App\Models\VideoSubCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Carbon\Carbon;

class SitemapGenerator implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_URLS_PER_FILE = 5000;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->generateUserSitemaps();
        $this->generateVideoSitemaps();
        $this->generateCategorySitemaps();
        $this->generateSubCategorySitemaps();
    }

    /**
     * Generate user sitemaps with URL limit handling
     *
     * @return void
     */
    private function generateUserSitemaps()
    {
        $templates = json_decode(file_get_contents(storage_path('app/sitemap/templates.json')), true);

        if (!isset($templates['citizens'])) {
            return;
        }

        $citizensTemplates = $templates['citizens'];
        $urlsPerFile = [];
        $currentFileIndex = 1;
        $currentUrlCount = 0;
        $totalUrls = 0;

        User::chunk(200, function ($users) use ($citizensTemplates, &$urlsPerFile, &$currentFileIndex, &$currentUrlCount, &$totalUrls) {
            foreach ($users as $user) {
                $userUrls = $this->generateUserUrls($user, $citizensTemplates);

                foreach ($userUrls as $url) {
                    // Check if we need to start a new file
                    if ($currentUrlCount >= self::MAX_URLS_PER_FILE) {
                        $currentFileIndex++;
                        $currentUrlCount = 0;
                    }

                    if (!isset($urlsPerFile[$currentFileIndex])) {
                        $urlsPerFile[$currentFileIndex] = [];
                    }

                    $urlsPerFile[$currentFileIndex][] = $url;
                    $currentUrlCount++;
                    $totalUrls++;
                }
            }
        });

        // Write sitemap files
        foreach ($urlsPerFile as $fileIndex => $urls) {
            $sitemap = Sitemap::create();

            foreach ($urls as $url) {
                $sitemap->add($url);
            }

            $filename = $fileIndex === 1 ? 'citizen-sitemap.xml' : "citizen-sitemap-{$fileIndex}.xml";
            $sitemap->writeToDisk('ftp', $filename);

        }
    }

    /**
     * Generate URLs for a specific user
     *
     * @param User $user
     * @param array $citizensTemplates
     * @return array
     */
    private function generateUserUrls(User $user, array $citizensTemplates): array
    {
        $urls = [];

        // Process each language template
        foreach ($citizensTemplates as $language => $urlTemplates) {
            foreach ($urlTemplates as $urlTemplate) {
                // Replace placeholders in the template
                $processedUrl = str_replace('[code]', $user->code, $urlTemplate);

                // Create the sitemap URL
                $sitemapUrl = Url::create($processedUrl)
                    ->setLastModificationDate(Carbon::create($user->updated_at))
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                    ->setPriority(0.8);

                $urls[] = $sitemapUrl;
            }
        }

        return $urls;
    }

    /**
     * Generate video sitemaps
     *
     * @return void
     */
    private function generateVideoSitemaps()
    {
        $sitemap = Sitemap::create()->add(Video::all());
        $sitemap->writeToDisk('ftp', 'education_single_video-sitemap.xml');
    }

    /**
     * Generate category sitemaps
     *
     * @return void
     */
    private function generateCategorySitemaps()
    {
        $sitemap = Sitemap::create()->add(VideoCategory::all());
        $sitemap->writeToDisk('ftp', 'education_category-sitemap.xml');
    }

    /**
     * Generate sub-category sitemaps
     *
     * @return void
     */
    private function generateSubCategorySitemaps()
    {
        $sitemap = Sitemap::create()->add(VideoSubCategory::with('category')->get());
        $sitemap->writeToDisk('ftp', 'education_sub_category-sitemap.xml');
    }
}
