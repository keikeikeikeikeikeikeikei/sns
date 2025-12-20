<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require __DIR__ . '/config/database.php';

use App\Models\User;
use App\Models\Post;
use App\Models\Reaction;
use Illuminate\Database\Capsule\Manager as Capsule;

try {
    $user = User::where('username', 'testuser')->first();
    
    if (!$user) {
        echo "User 'testuser' not found. Please run seed_user.php first.\n";
        exit(1);
    }

    // Clean up existing posts for this user to ensure screenshots look exactly as requested
    Post::where('user_id', $user->id)->delete();
    echo "Cleared existing posts for testuser.\n";

    $texts = ['dsjafjsd', 'sdfhjさああ', 'asfdはsd', 'salndfjnas']; // Reverse order so they appear in correct order on feed (newest first)
    $emojis = ['👍', '❤️', '😂', '😮'];

    foreach ($texts as $index => $text) {
        $post = Post::create([
            'user_id' => $user->id,
            'type' => Post::TYPE_FEED,
            'content_short' => $text,
        ]);
        echo "Created post: $text\n";

        // Add a reaction
        $emoji = $emojis[$index % count($emojis)];
        Reaction::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'emoji' => $emoji,
        ]);
        echo "Added reaction $emoji to post $text\n";
    }
    
    // Create a Q&A post
    Post::create([
        'user_id' => $user->id,
        'type' => Post::TYPE_QA,
        'title' => 'sdhjkahasdあいうえお',
        'content_long' => 'asdklfjal;sdfjkasl;dfja;sdjf\nあいうえおかきくけこ\nsdfjkasdl;fjasdkl;fjasd',
        'qa_status' => Post::QA_STATUS_OPEN,
    ]);
    echo "Created QA post\n";

    // Create a Blog post
    Post::create([
        'user_id' => $user->id,
        'type' => Post::TYPE_BLOG,
        'title' => 'bnm,./zxcvbnmあいう',
        'content_long' => 'qwertyuiopasdfghjklzxcvbnm\nなにぬねのはひふへほ\n1234567890',
    ]);
    echo "Created Blog post\n";

} catch (\Exception $e) {
    echo "Error seeding posts: " . $e->getMessage() . "\n";
    exit(1);
}