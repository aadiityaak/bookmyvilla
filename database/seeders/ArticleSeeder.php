<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
  public function run(): void
  {
    $faker = fake();
    $faker->unique(true);

    $authorIds = User::query()
      ->whereIn('role', ['admin', 'host'])
      ->pluck('id')
      ->all();

    if (count($authorIds) === 0) {
      $authorIds = User::query()->pluck('id')->all();
    }

    if (count($authorIds) === 0) {
      return;
    }

    $statuses = ['draft', 'published', 'archived'];

    for ($i = 0; $i < 30; $i++) {
      $status = $i < 12 ? 'published' : $statuses[array_rand($statuses)];

      $title = Str::title($faker->unique()->words($faker->numberBetween(3, 7), true));

      $slugBase = Str::slug($title);
      $slug = $slugBase;
      $suffix = 2;
      while (Article::query()->where('slug', $slug)->exists()) {
        $slug = $slugBase . '-' . $suffix;
        $suffix++;
        if ($suffix > 200) {
          $slug = $slugBase . '-' . Str::random(6);
          break;
        }
      }

      $excerpt = $faker->optional(0.9)->text(180);

      $paragraphs = $faker->optional(0.9)->paragraphs($faker->numberBetween(4, 8));
      $content = is_array($paragraphs) && count($paragraphs)
        ? '<p>' . implode('</p><p>', $paragraphs) . '</p>'
        : null;

      $publishedAt = null;
      if ($status === 'published') {
        $publishedAt = $faker->dateTimeBetween('-45 days', 'now');
      }

      Article::create([
        'author_id' => $authorIds[array_rand($authorIds)],
        'title' => $title,
        'slug' => $slug,
        'featured_image' => $faker->optional($status === 'published' ? 0.95 : 0.7)->passthrough(
          'https://picsum.photos/seed/article-' . $faker->uuid() . '/1200/630'
        ),
        'excerpt' => $excerpt,
        'content' => $content,
        'status' => $status,
        'published_at' => $publishedAt,
      ]);
    }
  }
}
