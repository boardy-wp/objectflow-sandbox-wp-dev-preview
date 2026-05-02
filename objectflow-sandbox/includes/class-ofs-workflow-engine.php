<?php
if (!defined('ABSPATH')) { exit; }

class OFS_Workflow_Engine {
    public static function validate_definition($definition): array {
        $data = is_string($definition) ? json_decode($definition, true) : $definition;
        if (!is_array($data)) return [false, 'Invalid JSON'];
        foreach (['schemaVersion','key','name','startNode','nodes'] as $k) {
            if (empty($data[$k])) return [false, "Missing: {$k}"];
        }
        if (!is_array($data['nodes'])) return [false, 'nodes must be array'];
        $ids=[];
        foreach ($data['nodes'] as $node) {
            if (empty($node['id'])) return [false, 'Node missing id'];
            if (isset($ids[$node['id']])) return [false, 'Duplicate node id'];
            $ids[$node['id']]=true;
        }
        if (!isset($ids[$data['startNode']])) return [false, 'startNode not found'];
        foreach (($data['closedNodes'] ?? []) as $n) if (!isset($ids[$n])) return [false, 'closedNode not found'];
        foreach ($data['nodes'] as $node) foreach (($node['forms'] ?? []) as $form) foreach (($form['buttons'] ?? []) as $btn) {
            if (!empty($btn['targetNode']) && !isset($ids[$btn['targetNode']])) return [false, 'button target missing'];
        }
        return [true, 'Valid'];
    }
    public static function get_node($definition, $node_id): ?array {
        $data = is_string($definition) ? json_decode($definition, true) : $definition;
        if (!is_array($data) || empty($data['nodes'])) return null;
        foreach ($data['nodes'] as $node) if (($node['id'] ?? '') === $node_id) return $node;
        return null;
    }
    public static function get_start_node($definition): ?array {
        $data = is_string($definition) ? json_decode($definition, true) : $definition;
        return is_array($data) ? self::get_node($data, $data['startNode'] ?? '') : null;
    }
}
