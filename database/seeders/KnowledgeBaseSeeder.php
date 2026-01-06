<?php

namespace Database\Seeders;

use App\Models\KnowledgeBase;
use App\Models\RefCategory;
use App\Models\RefSubCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first category and subcategory for testing
        $category = RefCategory::first();
        $subCategory = RefSubCategory::first();

        if ($category && $subCategory) {
            $knowledgeBaseItems = [
                [
                    'uuid' => Str::uuid(),
                    'cat_id' => $category->id,
                    'subcat_id' => $subCategory?->id,
                    'kb_title' => 'How to Reset Your Password',
                    'kb_desc' => 'This article explains how to reset your password if you have forgotten it. Follow the step-by-step guide to regain access to your account safely.',
                    'kb_tag' => 'password,reset,login,security',
                    'kb_status' => 'aktif',
                    'created_by' => 1,
                ],
                [
                    'uuid' => Str::uuid(),
                    'cat_id' => $category->id,
                    'subcat_id' => $subCategory?->id,
                    'kb_title' => 'System Requirements',
                    'kb_desc' => 'Learn about the minimum system requirements needed to run this application efficiently. This includes browser compatibility, operating system requirements, and hardware specifications.',
                    'kb_tag' => 'system,requirements,compatibility',
                    'kb_status' => 'aktif',
                    'created_by' => 1,
                ],
                [
                    'uuid' => Str::uuid(),
                    'cat_id' => $category->id,
                    'subcat_id' => $subCategory?->id,
                    'kb_title' => 'Getting Started Guide',
                    'kb_desc' => 'A comprehensive guide for new users to get started with the system. This guide covers account setup, basic navigation, and essential features.',
                    'kb_tag' => 'guide,getting started,tutorial,basics',
                    'kb_status' => 'aktif',
                    'created_by' => 1,
                ],
                [
                    'uuid' => Str::uuid(),
                    'cat_id' => $category->id,
                    'subcat_id' => $subCategory->id,
                    'kb_title' => 'Troubleshooting Common Issues',
                    'kb_desc' => 'This article covers solutions to the most frequently encountered issues. Use this guide to quickly resolve common problems.',
                    'kb_tag' => 'troubleshooting,issues,problems,solutions',
                    'kb_status' => 'tidak aktif',
                    'created_by' => 1,
                ]
            ];

            foreach ($knowledgeBaseItems as $item) {
                KnowledgeBase::create($item);
            }

            $this->command->info('Knowledge Base sample data created successfully!');
        } else {
            $this->command->error('No categories or subcategories found. Please seed categories and subcategories first.');
        }
    }
}
