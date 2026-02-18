<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Check if user has a dating profile
$stmt = $pdo->prepare("SELECT * FROM dating_profiles WHERE user_id = ?");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $age = intval($_POST['age']);
    $gender = $_POST['gender'];
    $looking_for = $_POST['looking_for'];
    $bio = sanitize($_POST['bio']);
    $location = sanitize($_POST['location_name']);
    $interests = sanitize($_POST['interests']);
    $pic = sanitize($_POST['profile_pic'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400');

    try {
        if ($profile) {
            $stmt = $pdo->prepare("UPDATE dating_profiles SET age = ?, gender = ?, looking_for = ?, bio = ?, location_name = ?, interests = ?, profile_pic = ? WHERE user_id = ?");
            $stmt->execute([$age, $gender, $looking_for, $bio, $location, $interests, $pic, $user_id]);
            $msg = "Profile updated!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO dating_profiles (user_id, age, gender, looking_for, bio, location_name, interests, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $age, $gender, $looking_for, $bio, $location, $interests, $pic]);
            $msg = "Dating profile created!";
        }
        redirectWithMessage('dating.php', 'success', $msg);
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-lg rounded-5 overflow-hidden">
                <div class="bg-danger text-white p-5 text-center">
                    <h2 class="fw-bold mb-0">Love at First Sight?</h2>
                    <p class="opacity-75 mb-0">Complete your profile to start discovery</p>
                </div>
                <div class="card-body p-5">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger rounded-4">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <?php echo csrfField(); ?>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">My Age</label>
                                <input type="number" name="age" class="form-control rounded-pill border-0 bg-light px-4"
                                    value="<?php echo $profile['age'] ?? '25'; ?>" required min="18" max="99">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">I am a</label>
                                <select name="gender" class="form-select rounded-pill border-0 bg-light px-4" required>
                                    <option value="male" <?php echo ($profile['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($profile['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($profile['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Looking for</label>
                                <select name="looking_for" class="form-select rounded-pill border-0 bg-light px-4"
                                    required>
                                    <option value="female" <?php echo ($profile['looking_for'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="male" <?php echo ($profile['looking_for'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="everyone" <?php echo ($profile['looking_for'] ?? '') == 'everyone' ? 'selected' : ''; ?>>Everyone</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Short Bio</label>
                                <textarea name="bio" class="form-control border-0 bg-light px-4 py-3" rows="3"
                                    placeholder="Tell people about yourself..."
                                    style="border-radius:20px;"><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Interests (comma separated)</label>
                                <input type="text" name="interests"
                                    class="form-control rounded-pill border-0 bg-light px-4"
                                    placeholder="Music, Travel, Tech..."
                                    value="<?php echo htmlspecialchars($profile['interests'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Location Name</label>
                                <input type="text" name="location_name"
                                    class="form-control rounded-pill border-0 bg-light px-4"
                                    placeholder="e.g. Addis Ababa"
                                    value="<?php echo htmlspecialchars($profile['location_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Profile Picture URL</label>
                                <input type="url" name="profile_pic"
                                    class="form-control rounded-pill border-0 bg-light px-4" placeholder="https://..."
                                    value="<?php echo htmlspecialchars($profile['profile_pic'] ?? ''); ?>">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-danger btn-lg w-100 rounded-pill py-3 fw-bold mt-5 shadow">
                            Save Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>