<?php

namespace App\Domain\Competition\SignupRules\Support;

use App\Domain\Competition\Models\Competition;
use App\Models\User;

/**
 * Walks a rule tree of `{type: 'all'|'any', rules: [...]}` group nodes and
 * `{type: 'rule', key, config?}` leaf nodes, returning an aggregated result
 * with the list of unmet leaf-rule reasons.
 *
 * An empty/null tree is considered satisfied (no rules configured = open).
 *
 * Group semantics:
 *   - `all`: every child must be satisfied (AND).
 *   - `any`: at least one child must be satisfied (OR). If unsatisfied,
 *      the reasons from *all* failing children inside that OR group are
 *      collected so the user sees every alternative they could pursue.
 *
 * @see docs/mil-std-498/SRS.md COMP-F-014
 */
class SignupRuleEvaluator
{
    public function __construct(private readonly SignupRuleRegistry $registry) {}

    /**
     * @param  array<string, mixed>|null  $tree
     */
    public function evaluate(?array $tree, User $user, Competition $competition): SignupRuleEvaluationResult
    {
        if ($tree === null || $tree === []) {
            return new SignupRuleEvaluationResult(satisfied: true, unmetReasons: []);
        }

        $reasons = [];
        $satisfied = $this->evaluateNode($tree, $user, $competition, $reasons);

        return new SignupRuleEvaluationResult(
            satisfied: $satisfied,
            unmetReasons: $satisfied ? [] : array_values($this->deduplicate($reasons)),
        );
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<SignupRuleReason>  $reasons
     */
    private function evaluateNode(array $node, User $user, Competition $competition, array &$reasons): bool
    {
        $type = $node['type'] ?? 'rule';

        if ($type === 'all') {
            return $this->evaluateAll($node['rules'] ?? [], $user, $competition, $reasons);
        }

        if ($type === 'any') {
            return $this->evaluateAny($node['rules'] ?? [], $user, $competition, $reasons);
        }

        return $this->evaluateLeaf($node, $user, $competition, $reasons);
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @param  list<SignupRuleReason>  $reasons
     */
    private function evaluateAll(array $children, User $user, Competition $competition, array &$reasons): bool
    {
        $satisfied = true;

        foreach ($children as $child) {
            if (! is_array($child)) {
                continue;
            }

            if (! $this->evaluateNode($child, $user, $competition, $reasons)) {
                $satisfied = false;
            }
        }

        return $satisfied;
    }

    /**
     * @param  list<array<string, mixed>>  $children
     * @param  list<SignupRuleReason>  $reasons
     */
    private function evaluateAny(array $children, User $user, Competition $competition, array &$reasons): bool
    {
        if ($children === []) {
            return true;
        }

        $childReasons = [];
        $anySatisfied = false;

        foreach ($children as $child) {
            if (! is_array($child)) {
                continue;
            }

            $local = [];
            if ($this->evaluateNode($child, $user, $competition, $local)) {
                $anySatisfied = true;
            } else {
                foreach ($local as $r) {
                    $childReasons[] = $r;
                }
            }
        }

        if (! $anySatisfied) {
            foreach ($childReasons as $r) {
                $reasons[] = $r;
            }
        }

        return $anySatisfied;
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<SignupRuleReason>  $reasons
     */
    private function evaluateLeaf(array $node, User $user, Competition $competition, array &$reasons): bool
    {
        $key = $node['key'] ?? null;
        if (! is_string($key) || ! $this->registry->has($key)) {
            return true;
        }

        $config = $node['config'] ?? [];
        if (! is_array($config)) {
            $config = [];
        }

        $rule = $this->registry->make($key, $config);

        if ($rule->isSatisfiedBy($user, $competition)) {
            return true;
        }

        $reasons[] = $rule->reason($user, $competition);

        return false;
    }

    /**
     * Dedupe by (ruleKey + messageKey + serialized params) so the same unmet
     * prerequisite isn't reported twice (e.g. when nested in multiple groups).
     *
     * @param  list<SignupRuleReason>  $reasons
     * @return list<SignupRuleReason>
     */
    private function deduplicate(array $reasons): array
    {
        $seen = [];
        $out = [];

        foreach ($reasons as $r) {
            $fingerprint = $r->ruleKey.'|'.$r->messageKey.'|'.serialize($r->params);
            if (isset($seen[$fingerprint])) {
                continue;
            }
            $seen[$fingerprint] = true;
            $out[] = $r;
        }

        return $out;
    }
}
