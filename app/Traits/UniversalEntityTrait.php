<?php
// app/Traits/UniversalEntityTrait.php

namespace App\Traits;

use App\Models\UserConnection;
use App\Models\FavouriteCompany;
use App\User;
use App\Company;

trait UniversalEntityTrait
{
    /**
     * Get entity by ID with type specification
     */
    public function getEntityById($id, $type = null)
    {
        if (!$id) {
            return null;
        }

        // If type is specified, search only in that table
        if ($type === 'user') {
            return User::find($id);
        }
        
        if ($type === 'company') {
            return Company::find($id);
        }

        // If type not specified, check both tables
        $user = User::find($id);
        if ($user) {
            return $user;
        }

        return Company::find($id);
    }

    /**
     * Get entity type (user or company)
     */
    public function getEntityType($id)
    {
        if (!$id) {
            return null;
        }

        if (User::where('id', $id)->exists()) {
            return 'user';
        }

        if (Company::where('id', $id)->exists()) {
            return 'company';
        }

        return null;
    }

    /**
     * Check if entity exists
     */
    public function entityExists($id, $type = null)
    {
        if ($type === 'user') {
            return User::where('id', $id)->exists();
        }
        
        if ($type === 'company') {
            return Company::where('id', $id)->exists();
        }

        return User::where('id', $id)->exists() || Company::where('id', $id)->exists();
    }

    /**
     * Get enriched entity data
     */
    public function getEnrichedEntityData($entity)
    {
        if (!$entity) {
            return null;
        }

        if ($entity instanceof Company) {
            return [
                'id' => $entity->id,
                'name' => $entity->name ?? 'Unknown Company',
                'email' => $entity->email ?? null,
                'usertype' => 'company',
                'headline' => $entity->description ?? null,
                'image' => $entity->logo ? asset('company_logos/' . $entity->logo) : null,
                'slug' => $entity->slug ?? null,
                'visibility_control' => $entity->visibility_control ?? 'public',
                'entity_type' => 'company',
            ];
        }

        if ($entity instanceof User) {
            // If user is company type, get company record
            if ($entity->usertype === 'company') {
                $company = Company::where('user_id', $entity->id)->first();
                if ($company) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'email' => $company->email ?? $entity->email,
                        'usertype' => 'company',
                        'headline' => $company->description,
                        'image' => $company->logo ? asset('company_logos/' . $company->logo) : null,
                        'slug' => $company->slug,
                        'visibility_control' => $company->visibility_control ?? 'public',
                        'entity_type' => 'company',
                    ];
                }
            }

            // Regular user
            $firstName = $entity->first_name ?? '';
            $lastName = $entity->last_name ?? '';
            $name = trim($firstName . ' ' . $lastName);
            $name = $name ?: ($entity->name ?? 'Unknown User');

            return [
                'id' => $entity->id,
                'name' => $name,
                'email' => $entity->email ?? null,
                'usertype' => $entity->usertype ?? 'user',
                'headline' => $entity->headline ?? null,
                'image' => $entity->image ? asset('user_images/' . $entity->image) : null,
                'slug' => null,
                'visibility_control' => $entity->visibility_control ?? 'public',
                'entity_type' => 'user',
            ];
        }

        return null;
    }

    /**
     * Create connection with entity types
     */
    public function createConnection($followerId, $followingId, $followerType, $followingType, $status = 'pending', $reason = null)
    {
        return UserConnection::create([
            'follower_id' => $followerId,
            'follower_type' => $followerType,
            'following_id' => $followingId,
            'following_type' => $followingType,
            'status' => $status,
            'reason' => $reason,
        ]);
    }

    /**
     * Find connection between entities
     */
    public function findConnection($followerId, $followingId, $followerType = null, $followingType = null)
    {
        $query = UserConnection::where('follower_id', $followerId)
            ->where('following_id', $followingId);

        if ($followerType) {
            $query->where('follower_type', $followerType);
        }

        if ($followingType) {
            $query->where('following_type', $followingType);
        }

        return $query->first();
    }

    /**
     * Find any connection between entities (either direction)
     */
    public function findAnyConnection($entity1Id, $entity2Id, $entity1Type = null, $entity2Type = null)
    {
        $query = UserConnection::where(function($q) use ($entity1Id, $entity2Id, $entity1Type, $entity2Type) {
            $q->where('follower_id', $entity1Id)
              ->where('following_id', $entity2Id);
            
            if ($entity1Type) {
                $q->where('follower_type', $entity1Type);
            }
            if ($entity2Type) {
                $q->where('following_type', $entity2Type);
            }
        })->orWhere(function($q) use ($entity1Id, $entity2Id, $entity1Type, $entity2Type) {
            $q->where('follower_id', $entity2Id)
              ->where('following_id', $entity1Id);
            
            if ($entity2Type) {
                $q->where('follower_type', $entity2Type);
            }
            if ($entity1Type) {
                $q->where('following_type', $entity1Type);
            }
        });

        return $query->first();
    }
}