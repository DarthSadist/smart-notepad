<?php
/**
 * Smart Notepad - COLLAPSIBLE NOTES (v2.7)
 * Toggle Expand/Collapse + Dynamic Indicators
 */

// --- 🛡️ HIDDEN API HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['api_action']) && $_GET['api_action'] === 'add_note') {
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $tags = isset($_POST['tags']) ? explode(',', $_POST['tags']) : [];
    
    if (!empty($content)) {
        $post_id = wp_insert_post([
            'post_type'    => 'note',
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_title'   => wp_trim_words($content, 10),
        ]);
        
        if ($post_id && !empty($tags)) {
            wp_set_post_tags($post_id, $tags, true);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'post_id' => $post_id]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f5f5f7;
            --card-bg: rgba(255, 255, 255, 0.7);
            --accent: #0071e3;
            --text: #1d1d1f;
            --text-secondary: #86868b;
            --blur: 30px;
            --radius: 22px;
            --collapsed-height: 180px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; 
            background-color: var(--bg) !important; color: var(--text);
            line-height: 1.5; -webkit-font-smoothing: antialiased;
        }

        .container { max-width: 680px; margin: 0 auto; padding: 60px 20px; }

        header.site-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; }
        h1.site-title { font-size: 34px; font-weight: 700; letter-spacing: -0.02em; }
        .description { color: var(--text-secondary); margin-top: 4px; font-size: 16px; }

        .menu-btn {
            background: #fff; border: 1px solid rgba(0,0,0,0.05); padding: 10px 22px; border-radius: 30px;
            font-size: 14px; font-weight: 600; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }

        /* Popup Overlay Menu */
        #menu-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255,255,255,0.85); backdrop-filter: blur(var(--blur));
            z-index: 10000; display: none; padding: 100px 40px; overflow-y: auto;
        }
        #menu-overlay.active { display: block; }
        .menu-grid { max-width: 600px; margin: 0 auto; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .menu-list a { font-size: 20px; font-weight: 600; color: var(--text); text-decoration: none; display: block; margin-bottom: 12px; }
        .tags-cloud a { background: rgba(0,0,0,0.05); color: var(--text); padding: 6px 14px; border-radius: 20px; font-size: 14px; text-decoration: none; margin: 5px; display: inline-block; }
        .close-btn { position: absolute; top: 40px; right: 40px; font-size: 36px; cursor: pointer; color: #888; }

        /* Notes Card */
        .note-card { 
            background: var(--card-bg); backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.4); border-radius: var(--radius);
            padding: 28px; margin-bottom: 24px; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.04);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;
            overflow: hidden; position: relative;
            scroll-margin-top: 80px; /* Отступ при скролле к началу карточки */
        }
        .note-card:hover { box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08); }

        .content-wrapper {
            max-height: var(--collapsed-height); overflow: hidden; position: relative;
            transition: max-height 0.7s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Gradient mask for collapsed state */
        .content-wrapper::after {
            content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 80px;
            background: linear-gradient(transparent, rgba(255,255,255,0.95));
            transition: opacity 0.4s; pointer-events: none;
        }

        .note-card.expanded .content-wrapper { max-height: 8000px; }
        .note-card.expanded .content-wrapper::after { opacity: 0; }

        .expand-indicator {
            text-align: center; color: var(--accent); font-size: 12px; font-weight: 600;
            margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.03);
            transition: all 0.3s;
        }

        .content { font-size: 17px; color: #111; }
        .content pre { 
            background: #1c1c1e; color: #f5f5f7; padding: 20px; border-radius: 14px;
            font-family: 'SFMono-Regular', Consolas, monospace; font-size: 14px; position: relative; margin: 16px 0;
        }

        .meta { display: flex; justify-content: space-between; align-items: center; margin-top: 24px; }
        .tag-link { background: rgba(0, 113, 227, 0.08); color: var(--accent); padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; }
        time { color: var(--text-secondary); font-size: 13px; }

        .copy-btn {
            position: absolute; right: 12px; top: 12px;
            background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(255,255,255,0.1);
            padding: 4px 10px; border-radius: 8px; cursor: pointer; font-size: 11px; z-index: 100;
        }
    </style>
</head>
<body <?php body_class(); ?>>

    <div id="menu-overlay">
        <div class="close-btn" onclick="toggleMenu()">&times;</div>
        <div class="menu-grid">
            <div class="menu-section">
                <h3>Разделы</h3>
                <ul class="menu-list">
                    <li><a href="<?php echo home_url('/'); ?>">Все заметки</a></li>
                    <?php
                    $categories = get_terms(['taxonomy' => 'note_category', 'hide_empty' => true]);
                    foreach ($categories as $cat) echo '<li><a href="' . get_term_link($cat) . '">' . $cat->name . '</a></li>';
                    ?>
                </ul>
            </div>
            <div class="menu-section">
                <h3>Тэги</h3>
                <div class="tags-cloud">
                    <?php
                    $tags = get_tags(['hide_empty' => true]);
                    foreach ($tags as $tag) echo '<a href="' . get_tag_link($tag) . '">#' . $tag->name . '</a>';
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <header class="site-header">
            <div>
                <h1 class="site-title"><?php bloginfo('name'); ?></h1>
                <p class="description"><?php bloginfo('description'); ?></p>
            </div>
            <button class="menu-btn" onclick="toggleMenu()">Меню</button>
        </header>

        <main>
            <?php
            $args = [ 'post_type' => 'note', 'post_status' => 'publish', 'posts_per_page' => 50 ];
            if (is_tax('note_category')) { $args['tax_query'] = [[ 'taxonomy' => 'note_category', 'field' => 'slug', 'terms' => get_query_var('term') ]]; }
            if (is_tag()) { $args['tag'] = get_query_var('tag'); }

            $query = new WP_Query($args);

            if ( $query->have_posts() ) :
                while ( $query->have_posts() ) : $query->the_post(); ?>
                    <article class="note-card" onclick="toggleNote(this, event)">
                        <div class="content-wrapper">
                            <div class="content">
                                <?php 
                                $content = get_the_content();
                                echo preg_replace('/<pre>/', '<pre><button class="copy-btn" onclick="copyCode(this, event)">Copy</button>', $content);
                                ?>
                            </div>
                        </div>
                        <div class="expand-indicator">Развернуть ↓</div>
                        <div class="meta">
                            <div class="tags">
                                <?php
                                if ($post_tags = get_the_tags()) {
                                    foreach ($post_tags as $tag) echo '<a href="' . get_tag_link($tag) . '" class="tag-link" onclick="event.stopPropagation()">#' . $tag->name . '</a>';
                                }
                                ?>
                            </div>
                            <time><?php echo get_the_date('d M, Y H:i'); ?></time>
                        </div>
                    </article>
                <?php endwhile; wp_reset_postdata(); ?>
            <?php else : echo '<p style="text-align:center; color:#888;">Заметок пока нет.</p>'; endif; ?>
        </main>
    </div>

    <?php wp_footer(); ?>

    <script>
    function toggleNote(card, event) {
        if (event.target.closest('.copy-btn') || event.target.closest('.tag-link')) return;
        
        const isExpanded = card.classList.toggle('expanded');
        const indicator = card.querySelector('.expand-indicator');
        indicator.innerText = isExpanded ? 'Свернуть ↑' : 'Развернуть ↓';
        
        if (!isExpanded) {
            // При сворачивании возвращаем скролл к началу карточки с учетом отступа
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function toggleMenu() {
        const overlay = document.getElementById('menu-overlay');
        overlay.classList.toggle('active');
        document.body.style.overflow = overlay.classList.contains('active') ? 'hidden' : '';
    }

    function copyCode(btn, event) {
        event.stopPropagation();
        const code = btn.parentElement.innerText.replace(/^Copy/, '').trim();
        navigator.clipboard.writeText(code).then(() => {
            btn.innerText = 'Copied!';
            btn.style.background = '#34c759';
            setTimeout(() => { btn.innerText = 'Copy'; btn.style.background = 'rgba(255,255,255,0.1)'; }, 2000);
        });
    }
    </script>
</body>
</html>
