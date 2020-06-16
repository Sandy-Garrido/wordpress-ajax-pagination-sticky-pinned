
$paged = $_POST['pageRequested'];

$pinned_post_arr = array(); 


$pinned_args = array(
    'post_type' => 'insight',
    'posts_per_page' => -1,
    'meta_key' => 'set_as_pinned',
    'meta_value' => true
); 


$pinned_posts = new \WP_Query($pinned_args);


    while ($pinned_posts->have_posts()){
        $pinned_posts->the_post();


        array_push( $pinned_post_arr,  get_the_ID()); 

        $Posts = new Posts;


        if($paged == 1){
          query_response['data'] .= $Posts->render_posts(); 
        }
    }
    wp_reset_postdata(); // ensure you include this line

if($paged == 1){

    // if the request from the browser ajax request wants page 1,
    // we need the second query to return the right amount of posts. 
    // This is done by deducting the count of our pinned post array
    $post_per_page = 10 - count($pinned_post_arr);
    $offset = false; // this is not req but I like to add for sanity


} else {

    // If the browser did not request page 1 but requested another page.
    // We need to counter the pinned posts using an offset.
    // This stops page 2 missing items which page 2 thinks is on page 1.


    $post_per_page = 10;
    $offset = ((10-count($pinned_post_arr) ) + (10 * ($paged - 2)));

}


$args = array(
    'post__not_in'      => $pinned_post_arr, // required -exclude already pinned posts
    'post_type'         => $post_type,
    'posts_per_page'    => $post_per_page, // required
    'post_status'       => 'publish',
    'orderby'           => $orderby,
    'order'             =>  $order,
    'offset'            => $offset, // required
    'paged'             => $paged, // required
    'tax_query'         => $tax_query, 
);


$wp_query = new \WP_Query($args);

$query_response['numOfItems'] = $wp_query->found_posts + count($pinned_post_arr);

$query_response['postsPerPage'] = 10;


while ($wp_query->have_posts()) {

    $wp_query->the_post();
    $Posts = new Posts;

    $query_response['data'] .= $Posts->render_posts();

}
wp_reset_postdata(); 
