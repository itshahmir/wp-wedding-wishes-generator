<?php
// Frontend functionalities

// Shortcode for displaying the form
function wedding_wishes_shortcode()
{
    ob_start();

    $user_ip = getUserIP();
    // Check if IP address already exists in the database
    global $wpdb;
    $table_name_functions = $wpdb->prefix . 'ip_restrict_wishes';
    $current_timestamp = time();
    $existing_ip = $wpdb->get_var($wpdb->prepare("SELECT ipAddress FROM $table_name_functions WHERE ipAddress = %s", $user_ip));
    // If IP doesn't exist, insert it into the database
    if (!$existing_ip) {
        $wpdb->insert(
            $table_name_functions,
            array(
                'ipAddress' => $user_ip,
                'no_of_requests' => 0, // Initial number of requests
                'status' => 'active',
                'last_request_timestamp' => $current_timestamp // You can set the initial status as needed
            )
        );
    }

    // Check if IP address already exists in the database
    $ip_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name_functions WHERE ipAddress = %s", $user_ip));
    // Check if the IP exists and if the number of requests is below the limit
    $limit_exceeded = $ip_data && $ip_data->no_of_requests >= get_option('wedding_wishes_limit');

    // Check if the 24-hour limit has been exceeded based on the timestamp
    if ($limit_exceeded) {
        $last_request_timestamp = $ip_data->last_request_timestamp;
        $twenty_four_hours_ago = $current_timestamp - (24 * 60 * 60);

        if ($last_request_timestamp < $twenty_four_hours_ago) {
            // If the timestamp is older than 24 hours, reset it to the current time
            $wpdb->query($wpdb->prepare("UPDATE $table_name_functions SET `no_of_requests` = 0, `last_request_timestamp` = $current_timestamp WHERE `ipAddress` = %s", $user_ip));
        }

        $limit_exceeded = $last_request_timestamp >= $twenty_four_hours_ago;
    }

    wp_enqueue_style('wedding-wishes-style'); // Enqueue the style here

?>
    <div class="well wedding-wishes-form">
        <?php if ($limit_exceeded) : ?>
            <p class="limit-exceeded-message">We appreciate your use of our Wedding Wish Generator! Regrettably, you've hit today's usage limit. Feel free to return in 24 hours.</p>
        <?php else : ?>
            <form id="weddingWishesForm" method="post">
                <div class="form-group">
                    <label for="brideName">Bride's Name:</label>
                    <input type="text" class="form-control" id="brideName" name="brideName" placeholder="Esther" required>
                </div>
                <div class="form-group">
                    <label for="groomName">Groom's Name:</label>
                    <input type="text" class="form-control" id="groomName" name="groomName" placeholder="Matthew" required>
                </div>
                <div class="form-group">
                    <label for="toneOfVoice">Tone of Voice:</label>
                    <select class="form-control" id="toneOfVoice" name="toneOfVoice" required>
                        <option value="Romantic">Romantic</option>
                        <option value="Humorous">Humorous</option>
                        <option value="Formal">Formal</option>
                        <option value="Inspirational">Inspirational</option>
                        <option value="Casual">Casual</option>
                        <option value="Poetic">Poetic</option>
                        <option value="Nostalgic">Nostalgic</option>
                        <option value="Sincere">Sincere</option>
                        <option value="Optimistic">Optimistic</option>
                        <option value="Whimsical">Whimsical</option>
                        <option value="Empathetic">Empathetic</option>
                        <option value="Elegant">Elegant</option>
                        <option value="Motivational">Motivational</option>
                        <option value="Joyful">Joyful</option>
                        <option value="Respectful">Respectful</option>
                        <option value="Adventurous">Adventurous</option>
                        <option value="Witty">Witty</option>
                        <option value="Mystical">Mystical</option>
                        <option value="Sentimental">Sentimental</option>
                        <option value="Solemn">Solemn</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="specialRequests">Keywords / Special Requests (optional):</label>
                    <textarea class="form-control" id="specialRequests" name="specialRequests" rows="3" placeholder="Include some Pokemon puns"></textarea>
                </div>
                <button type="submit"  class="button btn-primary save-button">Generate my Wedding Wish</button>
            </form>
            <div id="generatedWish" style="display:none;">
                <h3>Generated Wedding Wish:</h3>
                <p id="wishOutput"></p>
                <button type="button" class="button btn-primary repeat-button" onclick="regenerateWeddingWish()">Regenerate</button>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('weddingWishesForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission
            generateWeddingWish();
            
        });

        function generateWeddingWish() {

            var saveButton = document.querySelector(".save-button");
            var loader = document.createElement("span");
            loader.className = "loader";
            saveButton.appendChild(loader);
            saveButton.disabled = true;

            var brideName = document.getElementById('brideName').value;
            var groomName = document.getElementById('groomName').value;
            var toneOfVoice = document.getElementById('toneOfVoice').value;
            var specialRequests = document.getElementById('specialRequests').value;
            // Prompt For creating Wishes
            var search = "Create a wedding greeting for " + brideName + " and " + groomName + ". Tone: " + toneOfVoice + ". Keywords: " + specialRequests + " 50-word message";
            if (specialRequests) {
                search += ". " + specialRequests;
            }

            // Log the search string (for testing purposes)
            console.log(search);

            // Make API request
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {

                // Hide loader
                saveButton.removeChild(loader);
                // Enable button
                saveButton.disabled = false;

                    var response = JSON.parse(xhr.responseText);
                    if (response.content) {
                        displayGeneratedWish(response.content);
                    } else {
                        displayGeneratedWish(response.error);
                    }
                }
            };
            xhr.send('action=generate_wedding_wish&search=' + encodeURIComponent(search));
        }

        function displayGeneratedWish(wish) {
            console.log(wish);
            document.getElementById('wishOutput').innerText = wish;
            document.getElementById('generatedWish').style.display = 'block';
        }

        function regenerateWeddingWish() {

            var repeatButton = document.querySelector(".repeat-button");
            var loaders = document.createElement("span");
            loaders.className = "loader";
            repeatButton.appendChild(loaders);
            repeatButton.disabled = true;

            // Retrieve the parameters from the form
            var brideName = document.getElementById('brideName').value;
            var groomName = document.getElementById('groomName').value;
            var toneOfVoice = document.getElementById('toneOfVoice').value;
            var specialRequests = document.getElementById('specialRequests').value;

            // Construct the search string for the API request
            var search = "Create a wedding greeting for Bride: " + brideName + " and Groom: " + groomName + ". Tone Of Voice: " + toneOfVoice + ". Keywords: " + specialRequests + "  message Of: 50-words";
            if (specialRequests) {
                search += ". " + specialRequests;
            }

            // Log the search string (for testing purposes)
            console.log(search);

            // Make API request
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);

                    // Hide loader
                    repeatButton.removeChild(loaders);
                    // Enable button
                    repeatButton.disabled = false;

                    if (response.content) {
                        displayGeneratedWish(response.content);
                    } else {
                        displayGeneratedWish(response.error);
                    }
                }
            };
            xhr.send('action=generate_wedding_wish&search=' + encodeURIComponent(search));
        }
    </script>
<?php
    return ob_get_clean();
}

// AJAX handler for the API request
add_action('wp_ajax_generate_wedding_wish', 'generate_wedding_wish');
add_action('wp_ajax_nopriv_generate_wedding_wish', 'generate_wedding_wish');

function generate_wedding_wish()
{
    $openAISecretKey = "**************************";
    // Get user IP address
    $user_ip = getUserIP();

    // Check if IP address already exists in the database
    global $wpdb;
    $table_name_functions = $wpdb->prefix . 'ip_restrict_wishes';
    $ip_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name_functions WHERE ipAddress = %s", $user_ip));

    // Check if the IP exists and if the number of requests is below the limit
    if ($ip_data && $ip_data->no_of_requests < get_option('wedding_wishes_limit')) {

        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        if ($search) {
            $data = [
                "model" => "gpt-3.5-turbo",
                'messages' => [
                    [
                        "role" => "user",
                        "content" => $search
                    ]
                ],
                'temperature' => 0.5,
                "max_tokens" => 200,
                "top_p" => 1.0,
                "frequency_penalty" => 0.52,
                "presence_penalty" => 0.5,
                "stop" => ["11."],
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $openAISecretKey
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo json_encode(['error' => 'Error:' . curl_error($ch)]);
            } else {
                $response = json_decode($response, true);
                echo json_encode(['content' => $response['choices'][0]['message']['content']]);
                handle_successful_response();
            }

            curl_close($ch);
        } else {
            echo json_encode(['error' => 'Invalid request']);
        }
    } else {
        // Handle case where the limit is exceeded
        echo json_encode(['error' => 'You are only allowed to generate 5 wedding wishes a day. Please try again after 24 hours.']);
    }
    wp_die();
}

function handle_successful_response()
{
    $user_ip = getUserIP();
    global $wpdb;
    $current_timestamp = time();
    $table_name_functions = $wpdb->prefix . 'ip_restrict_wishes';
    $wpdb->query($wpdb->prepare("UPDATE $table_name_functions SET no_of_requests = no_of_requests + 1, `last_request_timestamp` = $current_timestamp WHERE ipAddress = %s", $user_ip));
}

// Hook functions to appropriate actions
add_shortcode('wedding_wishes_generator', 'wedding_wishes_shortcode');
