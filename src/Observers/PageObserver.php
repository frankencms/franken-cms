<?php

namespace FrankenCms\Observers;

use FrankenCms\Models\Page;
use FrankenCms\Services\PageRouteService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PageObserver
{
    public function creating(Page $post): void
    {
        if (! $post->post_author_id) {
            $post->post_author_id = auth()->user()->id;
        }

        // Auto-fill route_name from hierarchical path if not provided
        if (empty($post->route_name) && ! empty($post->post_slug)) {
            $post->route_name = $this->buildHierarchicalRouteName($post);
        }
    }

    public function created(Page $post): void
    {
        // Clear route cache after page is created
        app(PageRouteService::class)->clearCache();
    }

    public function updating(Page $post): void
    {
        // Regenerate route_name if slug or parent changed, or if route_name is empty
        $shouldRegenerate = empty($post->route_name)
            || $post->isDirty('post_slug')
            || $post->isDirty('parent_id');

        if ($shouldRegenerate && ! empty($post->post_slug)) {
            $post->route_name = $this->buildHierarchicalRouteName($post);
        }
    }

    public function updated(Page $post): void
    {
        // Cascade route_name updates to children when slug or parent changes
        if ($post->wasChanged(['post_slug', 'parent_id'])) {
            $this->cascadeRouteNameUpdates($post);
        }

        // Clear route cache when page is updated (route_name, slug, or status might change)
        if ($post->wasChanged(['route_name', 'post_slug', 'post_status', 'parent_id'])) {
            app(PageRouteService::class)->clearCache();
        }
    }

    public function deleted(Page $post): void
    {
        // Clear route cache when page is deleted
        app(PageRouteService::class)->clearCache();
    }

    /**
     * Build a hierarchical route name using dot notation (e.g., "company.team.leadership")
     * Uses a single query with recursive CTE to fetch all ancestors.
     */
    private function buildHierarchicalRouteName(Page $post): string
    {
        $ancestorSlugs = $this->getAncestorSlugs($post->parent_id);
        $ancestorSlugs[] = $post->post_slug;

        return implode('.', $ancestorSlugs);
    }

    /**
     * Get all ancestor slugs in root-first order using a single recursive CTE query.
     *
     * @return list<string>
     */
    private function getAncestorSlugs(?int $parentId): array
    {
        if (! $parentId) {
            return [];
        }

        $query = <<<'SQL'
            WITH RECURSIVE ancestors AS (
                SELECT id, post_slug, parent_id, 1 as level
                FROM posts
                WHERE id = ?

                UNION ALL

                SELECT p.id, p.post_slug, p.parent_id, a.level + 1
                FROM posts p
                INNER JOIN ancestors a ON p.id = a.parent_id
            )
            SELECT post_slug
            FROM ancestors
            ORDER BY level DESC
        SQL;

        return collect(DB::select($query, [$parentId]))
            ->pluck('post_slug')
            ->all();
    }

    /**
     * Get all descendant page IDs using a single recursive CTE query.
     *
     * @return Collection<int, int>
     */
    private function getDescendantIds(int $pageId): Collection
    {
        $query = <<<'SQL'
            WITH RECURSIVE descendants AS (
                SELECT id, parent_id
                FROM posts
                WHERE parent_id = ?

                UNION ALL

                SELECT p.id, p.parent_id
                FROM posts p
                INNER JOIN descendants d ON p.parent_id = d.id
            )
            SELECT id FROM descendants
        SQL;

        return collect(DB::select($query, [$pageId]))->pluck('id');
    }

    /**
     * Update route_names for all descendant pages when a parent's slug or hierarchy changes.
     */
    private function cascadeRouteNameUpdates(Page $post): void
    {
        $descendantIds = $this->getDescendantIds($post->id);

        if ($descendantIds->isEmpty()) {
            return;
        }

        // Fetch all descendants and update their route_names
        $descendants = Page::withoutGlobalScopes()
            ->whereIn('id', $descendantIds)
            ->get();

        foreach ($descendants as $descendant) {
            $descendant->route_name = $this->buildHierarchicalRouteName($descendant);
            $descendant->saveQuietly(); // Avoid triggering observers recursively
        }
    }
}
