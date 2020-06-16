I have managed to achieve this not by using the meta query method but actually by using 2 queries, which I didn't think was possible. I'll do my best to explain for others who may be having the same issue.

This solution will help people trying to solve

 - AJAX Pagination with pinned or sticky posts
 - Sticky Posts with custom post types


# Pre-req - Setting meta data for pinned / sticky
I will presume that you have already (with ACF or other means) set meta data to retrieve pinned posts. In my example, I have a field type of Boolean for a Key named `set_as_pinned`


# Step 1 - Get your pinned/stick posts 

```php
$paged = $_POST['pageRequested'];
```

```php
// declare an empty array for your pinned posts
// (this helps us exclude it from our query when we need to get all the other posts)
$pinned_post_arr = array(); 

// Define your args for pinned or sticky posts
$pinned_args = array(
    'post_type' => 'insight',
    'posts_per_page' => -1,
    'meta_key' => 'set_as_pinned',
    'meta_value' => true
); 

// Query list of pinned posts using wp_query or get_posts()
$pinned_posts = new \WP_Query($pinned_args);


    while ($pinned_posts->have_posts()){
        $pinned_posts->the_post();

//Send this post ID to array so we don't duplicate this in our list
        array_push( $pinned_post_arr,  get_the_ID()); 

        $Posts = new Posts;

        //add each post to the query response data
        if($paged == 1){

           // DO STUFF WITH YOUR PINNED POSTS HERE.
           // Generate HTML or cards, whatever you need
           // My use case was to generate HTML and pass it back via AJAX

// this is relative to my project, so copying won't work
          query_response['data'] .= $Posts->render_posts(); 

        }
    }
    wp_reset_postdata(); // ensure you include this line
```

# Step 2 - Set variables for the second query
(and calculate an offset for page 2 and up)

## What is offset?
An argument for `get_posts()` & `wp_query` 
> offset (int) – number of post to displace or pass over. Warning:
> Setting the offset parameter overrides/ignores the paged parameter and
> breaks pagination. The 'offset' parameter is ignored when
> 'posts_per_page'=>-1 (show all posts) is used.

**Take note of the warning Wordpress has given us of the paged and pagination issues.**

```php
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

// here, we calculate, based on the page we're on and how many pinned posts there were
// the number 10 is my defined posts per page that I personally wanted.
    $offset = ((10-count($pinned_post_arr) ) + (10 * ($paged - 2)));

}
```

# Step 3 - The second query

```php
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


// I used a library called SimplePagination
// https://github.com/flaviusmatis/simplePagination.js
// This required me to send back the number of items.
// I can query found posts as an int but I MUST add the count from pinned posts too

$query_response['numOfItems'] = $wp_query->found_posts + count($pinned_post_arr);

$query_response['postsPerPage'] = 10;


// Get all found posts (not including pinned) and add it to the data query
while ($wp_query->have_posts()) {

    $wp_query->the_post();
    $Posts = new Posts;

// Here I'm appending more to the AJAX response. Which will follow seamlessly from the pinned posts (if any)
    $query_response['data'] .= $Posts->render_posts();

}
wp_reset_postdata(); // reset for safe measures
```

I hope this helps you as it did me..
