<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\PulseKnowledgeEdge;
use App\Models\PulseKnowledgeNode;
use App\Models\PulsePost;
use App\Models\PulsePostEnrichment;

class KnowledgeGraphService
{
    /**
     * Extract knowledge nodes and edges from an enriched post.
     * Called after AiModerationService completes enrichment.
     */
    public function extractFromPost(PulsePost $post, PulsePostEnrichment $enrichment): void
    {
        if ($enrichment->moderation_status !== 'approved') {
            return;
        }

        // Create a "concept" node for each topic tag
        $topics   = $enrichment->topics  ?? [];
        $keywords = $enrichment->keywords ?? [];

        $topicNodes = collect($topics)->take(5)->map(function (string $topic) use ($post) {
            return $this->upsertNode('concept', $topic, null, [
                'post_id' => $post->id,
            ]);
        });

        // If post type is 'question', create a problem node
        if ($post->type === 'question') {
            $problemNode = $this->upsertNode('problem', $post->title ?? Str::limit($post->body, 80));
            foreach ($topicNodes as $topicNode) {
                $this->upsertEdge($problemNode->id, $topicNode->id, 'relates_to');
            }
        }

        // If post type is 'success_story' or 'showcase', link to products
        if (in_array($post->type, ['success_story', 'showcase', 'release'])) {
            $productNode = $this->upsertNode('product', $post->title ?? 'Unnamed Product', $post->body);
            foreach ($topicNodes as $topicNode) {
                $this->upsertEdge($productNode->id, $topicNode->id, 'features');
            }
        }

        // Link expert (post author) to topics if they have solutions_accepted > 0
        $profile = \App\Models\PulseProfile::where('user_id', $post->user_id)->first();
        if ($profile && $profile->solutions_accepted > 0) {
            $expertNode = $this->upsertNode('expert', $post->author->name, null, [
                'user_id' => $post->user_id,
            ]);
            foreach ($topicNodes->take(3) as $topicNode) {
                $this->upsertEdge($expertNode->id, $topicNode->id, 'expert_in', min(1.0, $profile->solutions_accepted / 10));
            }
        }
    }

    private function upsertNode(string $entityType, string $label, ?string $description = null, array $sources = []): PulseKnowledgeNode
    {
        return PulseKnowledgeNode::firstOrCreate(
            ['entity_type' => $entityType, 'label' => $label],
            [
                'description'      => $description,
                'sources'          => $sources,
                'confidence_score' => 50,
            ]
        );
    }

    private function upsertEdge(int $fromId, int $toId, string $relationship, float $weight = 1.0): PulseKnowledgeEdge
    {
        return PulseKnowledgeEdge::firstOrCreate(
            ['from_node_id' => $fromId, 'to_node_id' => $toId, 'relationship' => $relationship],
            ['weight' => $weight]
        );
    }
}
