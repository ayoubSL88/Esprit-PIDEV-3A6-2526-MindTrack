#!/usr/bin/env python
import json
import sys
from pathlib import Path

import cv2
import numpy as np


def emit_error(message: str, code: int = 2) -> int:
    print(json.dumps({"error": message}))
    return code


def clamp(value: float, low: int, high: int) -> int:
    return max(low, min(high, int(round(value))))


def safe_ratio(numerator: float, denominator: float) -> float:
    if denominator <= 0:
        return 0.0
    return float(numerator) / float(denominator)


def get_cascade(name: str) -> cv2.CascadeClassifier:
    cascade_path = Path(cv2.data.haarcascades) / name
    cascade = cv2.CascadeClassifier(str(cascade_path))
    if cascade.empty():
        raise RuntimeError(f"OpenCV cascade could not be loaded: {name}")
    return cascade


def resize_if_needed(image: np.ndarray, max_dimension: int = 1280) -> np.ndarray:
    height, width = image.shape[:2]
    largest_dimension = max(height, width)
    if largest_dimension <= max_dimension:
        return image

    scale = max_dimension / float(largest_dimension)
    resized = cv2.resize(
        image,
        (max(1, int(round(width * scale))), max(1, int(round(height * scale)))),
        interpolation=cv2.INTER_AREA,
    )
    return resized


def detect_faces(gray: np.ndarray) -> list[tuple[int, int, int, int]]:
    cascades = (
        ("haarcascade_frontalface_default.xml", False, 1.1, 6),
        ("haarcascade_frontalface_alt.xml", False, 1.08, 5),
        ("haarcascade_profileface.xml", False, 1.1, 5),
        ("haarcascade_profileface.xml", True, 1.1, 5),
    )
    candidates: list[tuple[int, int, int, int]] = []

    for cascade_name, flip, scale_factor, min_neighbors in cascades:
        target_gray = cv2.flip(gray, 1) if flip else gray
        cascade = get_cascade(cascade_name)
        detections = cascade.detectMultiScale(
            target_gray,
            scaleFactor=scale_factor,
            minNeighbors=min_neighbors,
            minSize=(80, 80),
        )

        for x, y, w, h in detections:
            if flip:
                x = gray.shape[1] - x - w
            candidates.append((int(x), int(y), int(w), int(h)))

    return candidates


def dedupe_faces(faces: list[tuple[int, int, int, int]]) -> list[tuple[int, int, int, int]]:
    deduped: list[tuple[int, int, int, int]] = []
    for face in sorted(faces, key=lambda item: item[2] * item[3], reverse=True):
        x, y, w, h = face
        duplicate = False
        for dx, dy, dw, dh in deduped:
            intersection_w = max(0, min(x + w, dx + dw) - max(x, dx))
            intersection_h = max(0, min(y + h, dy + dh) - max(y, dy))
            intersection = intersection_w * intersection_h
            union = (w * h) + (dw * dh) - intersection
            if union > 0 and (intersection / union) > 0.35:
                duplicate = True
                break

        if not duplicate:
            deduped.append(face)

    return deduped


def detect_primary_face(image: np.ndarray) -> tuple[np.ndarray, dict]:
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    equalized = cv2.equalizeHist(gray)
    clahe = cv2.createCLAHE(clipLimit=2.2, tileGridSize=(8, 8)).apply(gray)
    faces = dedupe_faces(detect_faces(equalized) + detect_faces(clahe))

    if len(faces) == 0:
        raise ValueError("No clear face was detected. Face the camera directly and try again.")

    x, y, w, h = max(faces, key=lambda face: int(face[2]) * int(face[3]))
    right = min(image.shape[1], x + w)
    bottom = min(image.shape[0], y + h)
    face = image[y:bottom, x:right]

    if face.size == 0:
        raise RuntimeError("The detector could not isolate the face region.")

    return face, {"x": int(x), "y": int(y), "w": int(w), "h": int(h)}


def detect_eyes(gray_face: np.ndarray) -> list[tuple[int, int, int, int]]:
    upper_face = gray_face[0:max(1, int(gray_face.shape[0] * 0.58)), :]
    eye_cascade = get_cascade("haarcascade_eye.xml")
    detections = eye_cascade.detectMultiScale(
        upper_face,
        scaleFactor=1.08,
        minNeighbors=8,
        minSize=(max(18, gray_face.shape[1] // 10), max(12, gray_face.shape[0] // 12)),
    )

    eyes: list[tuple[int, int, int, int]] = []
    for x, y, w, h in detections:
        center_x = x + (w / 2.0)
        center_y = y + (h / 2.0)
        if center_y > upper_face.shape[0] * 0.9:
            continue
        eyes.append((int(x), int(y), int(w), int(h)))

    eyes.sort(key=lambda item: item[2] * item[3], reverse=True)
    filtered: list[tuple[int, int, int, int]] = []
    for eye in eyes:
        if len(filtered) >= 2:
            break
        if not filtered:
            filtered.append(eye)
            continue

        previous = filtered[0]
        if abs((eye[0] + eye[2] / 2.0) - (previous[0] + previous[2] / 2.0)) < gray_face.shape[1] * 0.12:
            continue
        filtered.append(eye)

    filtered.sort(key=lambda item: item[0])
    return filtered


def align_face(face_image: np.ndarray) -> tuple[np.ndarray, list[tuple[int, int, int, int]]]:
    gray_face = cv2.cvtColor(face_image, cv2.COLOR_BGR2GRAY)
    gray_face = cv2.equalizeHist(gray_face)
    eyes = detect_eyes(gray_face)

    if len(eyes) < 2:
        return face_image, eyes

    left_eye, right_eye = eyes[0], eyes[1]
    left_center = (left_eye[0] + left_eye[2] / 2.0, left_eye[1] + left_eye[3] / 2.0)
    right_center = (right_eye[0] + right_eye[2] / 2.0, right_eye[1] + right_eye[3] / 2.0)
    dy = right_center[1] - left_center[1]
    dx = right_center[0] - left_center[0]
    angle = np.degrees(np.arctan2(dy, dx))
    rotate_center = ((left_center[0] + right_center[0]) / 2.0, (left_center[1] + right_center[1]) / 2.0)
    rotation = cv2.getRotationMatrix2D(rotate_center, angle, 1.0)
    aligned = cv2.warpAffine(
        face_image,
        rotation,
        (face_image.shape[1], face_image.shape[0]),
        flags=cv2.INTER_LINEAR,
        borderMode=cv2.BORDER_REPLICATE,
    )
    aligned_gray = cv2.cvtColor(aligned, cv2.COLOR_BGR2GRAY)
    aligned_gray = cv2.equalizeHist(aligned_gray)
    aligned_eyes = detect_eyes(aligned_gray)
    return aligned, aligned_eyes or eyes


def compute_image_quality(face_image: np.ndarray) -> dict:
    gray = cv2.cvtColor(face_image, cv2.COLOR_BGR2GRAY)
    brightness = float(np.mean(gray))
    contrast = float(np.std(gray))
    sharpness = float(cv2.Laplacian(gray, cv2.CV_64F).var())
    blur_score = max(0.0, 1.0 - min(sharpness / 180.0, 1.0))
    dark_score = max(0.0, (105.0 - brightness) / 55.0) if brightness < 105.0 else 0.0
    face_quality = min(
        1.0,
        (min(1.0, sharpness / 160.0) * 0.45)
        + (min(1.0, brightness / 150.0) * 0.35)
        + (min(1.0, contrast / 55.0) * 0.2),
    )

    return {
        "brightness": round(brightness, 2),
        "contrast": round(contrast, 2),
        "sharpness": round(sharpness, 2),
        "blur_score": round(blur_score, 4),
        "dark_score": round(dark_score, 4),
        "face_quality": round(face_quality, 4),
    }


def compute_mouth_features(gray_face: np.ndarray) -> dict:
    height, width = gray_face.shape
    mouth = gray_face[int(height * 0.56): int(height * 0.92), int(width * 0.16): int(width * 0.84)]
    if mouth.size == 0:
        return {
            "mouth_curve": 0.0,
            "mouth_openness": 0.0,
            "teeth_score": 0.0,
            "mouth_contrast": 0.0,
        }

    mouth_blur = cv2.GaussianBlur(mouth, (5, 5), 0)
    _, dark_mask = cv2.threshold(mouth_blur, 0, 255, cv2.THRESH_BINARY_INV + cv2.THRESH_OTSU)
    dark_mask = cv2.morphologyEx(dark_mask, cv2.MORPH_OPEN, np.ones((3, 3), np.uint8))
    row_weights = dark_mask.sum(axis=1).astype(np.float64)

    if float(row_weights.sum()) > 0:
        y_positions = np.arange(len(row_weights), dtype=np.float64)
        center_y = float(np.dot(y_positions, row_weights) / row_weights.sum())
    else:
        center_y = mouth.shape[0] / 2.0

    thirds = np.array_split(np.arange(mouth.shape[1]), 3)
    region_centers = []
    for region in thirds:
        if len(region) == 0:
            region_centers.append(center_y)
            continue
        region_mask = dark_mask[:, region]
        region_rows = region_mask.sum(axis=1).astype(np.float64)
        if float(region_rows.sum()) <= 0:
            region_centers.append(center_y)
            continue
        region_centers.append(float(np.dot(np.arange(len(region_rows), dtype=np.float64), region_rows) / region_rows.sum()))

    corner_average = (region_centers[0] + region_centers[2]) / 2.0
    mouth_curve = max(-1.0, min(1.0, (center_y - corner_average) / max(1.0, mouth.shape[0] * 0.22)))

    bright_mask = cv2.inRange(mouth_blur, 180, 255)
    teeth_score = min(1.0, float(bright_mask.mean()) / 48.0)
    mouth_contrast = min(1.0, float(np.std(mouth)) / 64.0)

    center_column_band = mouth[:, int(mouth.shape[1] * 0.35): int(mouth.shape[1] * 0.65)]
    if center_column_band.size == 0:
        mouth_openness = 0.0
    else:
        vertical_profile = center_column_band.mean(axis=1)
        profile_gradient = np.abs(np.gradient(vertical_profile))
        mouth_openness = min(1.0, float(profile_gradient.mean()) / 18.0)

    return {
        "mouth_curve": round(float(mouth_curve), 4),
        "mouth_openness": round(float(mouth_openness), 4),
        "teeth_score": round(float(teeth_score), 4),
        "mouth_contrast": round(float(mouth_contrast), 4),
    }


def compute_facial_features(face_image: np.ndarray, detected_eyes: list[tuple[int, int, int, int]]) -> dict:
    gray = cv2.cvtColor(face_image, cv2.COLOR_BGR2GRAY)
    gray = cv2.equalizeHist(gray)
    height, width = gray.shape

    upper_face = gray[0:max(1, int(height * 0.55)), :]
    lower_face = gray[int(height * 0.45):height, :]
    center_face = gray[int(height * 0.25):int(height * 0.75), int(width * 0.2):int(width * 0.8)]
    smile_cascade = get_cascade("haarcascade_smile.xml")
    smiles = smile_cascade.detectMultiScale(
        lower_face,
        scaleFactor=1.2,
        minNeighbors=20,
        minSize=(max(30, width // 4), max(18, height // 10)),
    )

    eye_count = min(2, len(detected_eyes))
    smile_strength = 0.0
    if len(smiles) > 0:
        largest_smile = max(smiles, key=lambda item: int(item[2]) * int(item[3]))
        smile_strength = min(
            1.0,
            safe_ratio(largest_smile[2], width * 0.6) * 0.7 + safe_ratio(len(smiles), 3) * 0.3,
        )

    eye_openness = 0.0
    if eye_count > 0:
        openness_scores = [safe_ratio(eye[3], max(1, eye[2])) for eye in detected_eyes[:2]]
        eye_openness = min(1.0, float(np.mean(openness_scores)) / 0.42)

    edge_density = float(cv2.Canny(center_face, 70, 160).mean()) / 255.0
    lower_brightness = float(np.mean(lower_face)) / 255.0
    upper_brightness = float(np.mean(upper_face)) / 255.0
    symmetry_gap = abs(
        float(np.mean(gray[:, : width // 2])) - float(np.mean(gray[:, width // 2 :]))
    ) / 255.0
    local_contrast = min(1.0, float(np.std(cv2.Laplacian(gray, cv2.CV_64F))) / 48.0)
    mouth_features = compute_mouth_features(gray)

    return {
        "eye_count": eye_count,
        "eye_presence": round(min(1.0, eye_count / 2.0), 4),
        "eye_openness": round(float(eye_openness), 4),
        "smile_strength": round(smile_strength, 4),
        "edge_density": round(edge_density, 4),
        "lower_brightness": round(lower_brightness, 4),
        "upper_brightness": round(upper_brightness, 4),
        "symmetry_gap": round(symmetry_gap, 4),
        "local_contrast": round(float(local_contrast), 4),
        **mouth_features,
    }


def infer_emotions(features: dict, quality: dict) -> dict:
    smile_strength = float(features["smile_strength"])
    eye_presence = float(features["eye_presence"])
    eye_openness = float(features["eye_openness"])
    edge_density = float(features["edge_density"])
    lower_brightness = float(features["lower_brightness"])
    upper_brightness = float(features["upper_brightness"])
    symmetry_gap = float(features["symmetry_gap"])
    mouth_curve = float(features["mouth_curve"])
    mouth_openness = float(features["mouth_openness"])
    teeth_score = float(features["teeth_score"])
    mouth_contrast = float(features["mouth_contrast"])
    local_contrast = float(features["local_contrast"])
    blur_score = float(quality["blur_score"])
    dark_score = float(quality["dark_score"])
    face_quality = float(quality["face_quality"])

    smile_signal = max(0.0, smile_strength * 0.45 + mouth_curve * 0.32 + teeth_score * 0.23)
    relaxed_signal = max(0.0, face_quality * 0.4 + eye_openness * 0.24 + (1.0 - symmetry_gap) * 0.12)
    strain_signal = max(0.0, edge_density * 0.34 + symmetry_gap * 0.18 + mouth_contrast * 0.16 + local_contrast * 0.12)

    happy = min(1.0, smile_signal * 0.72 + relaxed_signal * 0.2 + eye_presence * 0.08)
    tired = min(1.0, blur_score * 0.27 + dark_score * 0.22 + (1.0 - eye_presence) * 0.14 + (1.0 - eye_openness) * 0.2 + (1.0 - face_quality) * 0.1 + mouth_openness * 0.08)
    sad = min(1.0, max(0.0, (1.0 - smile_signal) * 0.28 + dark_score * 0.22 + max(0.0, 0.55 - lower_brightness) * 0.32 + (1.0 - eye_openness) * 0.12 + symmetry_gap * 0.12))
    anxious = min(1.0, max(0.0, strain_signal * 0.56 + eye_presence * 0.08 + mouth_openness * 0.12 + (1.0 - smile_signal) * 0.16))
    neutral = min(
        1.0,
        max(
            0.0,
            0.42
            + face_quality * 0.2
            + eye_presence * 0.05
            + eye_openness * 0.04
            - happy * 0.35
            - sad * 0.18
            - anxious * 0.12,
        ),
    )

    raw = {
        "happy": happy,
        "neutral": neutral,
        "sad": sad,
        "angry": anxious * 0.42,
        "fear": anxious * 0.38,
        "disgust": anxious * 0.2,
        "surprise": max(0.0, min(1.0, eye_presence * 0.35 + edge_density * 0.22 - smile_strength * 0.12)),
        "tired": tired,
        "upper_lower_balance": max(0.0, upper_brightness - lower_brightness),
    }

    base_total = raw["happy"] + raw["neutral"] + raw["sad"] + raw["angry"] + raw["fear"] + raw["disgust"] + raw["surprise"]
    if base_total <= 0:
        return {
            "angry": 0.0,
            "disgust": 0.0,
            "fear": 0.0,
            "happy": 0.0,
            "sad": 0.0,
            "surprise": 0.0,
            "neutral": 100.0,
            "tired_hint": round(tired * 100.0, 4),
        }

    return {
        "angry": round(raw["angry"] / base_total * 100.0, 4),
        "disgust": round(raw["disgust"] / base_total * 100.0, 4),
        "fear": round(raw["fear"] / base_total * 100.0, 4),
        "happy": round(raw["happy"] / base_total * 100.0, 4),
        "sad": round(raw["sad"] / base_total * 100.0, 4),
        "surprise": round(raw["surprise"] / base_total * 100.0, 4),
        "neutral": round(raw["neutral"] / base_total * 100.0, 4),
        "tired_hint": round(tired * 100.0, 4),
    }


def classify(emotions: dict, quality: dict, features: dict) -> tuple[dict, dict]:
    happy = emotions["happy"] / 100.0
    neutral = emotions["neutral"] / 100.0
    sad = emotions["sad"] / 100.0
    angry = emotions["angry"] / 100.0
    fear = emotions["fear"] / 100.0
    disgust = emotions["disgust"] / 100.0
    surprise = emotions["surprise"] / 100.0

    blur_score = float(quality["blur_score"])
    dark_score = float(quality["dark_score"])
    face_quality = float(quality["face_quality"])
    smile_strength = float(features["smile_strength"])
    eye_presence = float(features["eye_presence"])
    eye_openness = float(features["eye_openness"])
    mouth_curve = float(features["mouth_curve"])
    mouth_openness = float(features["mouth_openness"])
    teeth_score = float(features["teeth_score"])
    tired_hint = float(emotions.get("tired_hint", 0.0)) / 100.0

    anxious = max(angry, fear, (disgust * 0.85), (surprise * 0.45))
    tired = max(
        tired_hint,
        0.0,
        (neutral * 0.34)
        + (sad * 0.22)
        + (blur_score * 0.34)
        + (dark_score * 0.28)
        + ((1.0 - eye_presence) * 0.18)
        + ((1.0 - eye_openness) * 0.12)
        - (happy * 0.22)
        - (smile_strength * 0.1),
    )

    scores = {
        "happy": happy + (smile_strength * 0.12) + (max(0.0, mouth_curve) * 0.12) + (teeth_score * 0.08) + (face_quality * 0.04),
        "neutural": neutral + (face_quality * 0.06) + (eye_presence * 0.03) + (eye_openness * 0.03) - (sad * 0.05),
        "sad": sad + (dark_score * 0.1) + ((1.0 - smile_strength) * 0.04) + (max(0.0, -mouth_curve) * 0.08),
        "anxious": anxious + (surprise * 0.08) + (features["edge_density"] * 0.06) + (mouth_openness * 0.06),
        "tired": tired,
    }

    dominant_type = max(scores, key=scores.get)
    dominant_score = float(scores[dominant_type])

    if happy >= 0.3 and (smile_strength >= 0.26 or mouth_curve >= 0.14):
        dominant_type = "happy"
        dominant_score = scores["happy"]
    elif tired >= 0.54 and happy < 0.28 and eye_openness < 0.62:
        dominant_type = "tired"
        dominant_score = scores["tired"]
    elif anxious >= 0.28 and smile_strength < 0.18 and mouth_curve < 0.08:
        dominant_type = "anxious"
        dominant_score = scores["anxious"]
    elif sad >= 0.24 and smile_strength < 0.16 and mouth_curve < 0.02:
        dominant_type = "sad"
        dominant_score = scores["sad"]
    elif neutral >= 0.28:
        dominant_type = "neutural"
        dominant_score = scores["neutural"]

    score_margin = dominant_score - max(value for key, value in scores.items() if key != dominant_type)
    confidence = round(
        min(0.93, max(0.34, dominant_score * 0.9 + max(0.0, score_margin) * 0.32 + face_quality * 0.1)),
        2,
    )

    metrics = {
        "score_happy": round(scores["happy"], 4),
        "score_neutral": round(scores["neutural"], 4),
        "score_sad": round(scores["sad"], 4),
        "score_anxious": round(scores["anxious"], 4),
        "score_tired": round(scores["tired"], 4),
        "emotion_happy": round(happy, 4),
        "emotion_neutral": round(neutral, 4),
        "emotion_sad": round(sad, 4),
        "emotion_angry": round(angry, 4),
        "emotion_fear": round(fear, 4),
        "emotion_disgust": round(disgust, 4),
        "emotion_surprise": round(surprise, 4),
        "smile_strength": round(smile_strength, 4),
        "eye_presence": round(eye_presence, 4),
        "eye_openness": round(eye_openness, 4),
        "eye_count": int(features["eye_count"]),
        "edge_density": round(float(features["edge_density"]), 4),
        "lower_brightness": round(float(features["lower_brightness"]), 4),
        "upper_brightness": round(float(features["upper_brightness"]), 4),
        "symmetry_gap": round(float(features["symmetry_gap"]), 4),
        "mouth_curve": round(mouth_curve, 4),
        "mouth_openness": round(mouth_openness, 4),
        "teeth_score": round(teeth_score, 4),
        "mouth_contrast": round(float(features["mouth_contrast"]), 4),
        "local_contrast": round(float(features["local_contrast"]), 4),
    }
    metrics.update(quality)

    summaries = {
        "happy": "Detected positive visual cues, especially a smile pattern, that suggest a happier expression.",
        "neutural": "Detected a balanced expression without a strong enough smile or strain cue to justify a stronger label.",
        "sad": "Detected a lower-energy expression with softer lower-face cues and limited positive signal.",
        "anxious": "Detected tenser facial cues with limited smile evidence, which can fit a stressed or anxious state.",
        "tired": "Detected lower-energy facial cues with softer focus or darker tone that fit a tired look.",
    }

    intensities = {
        "happy": clamp(4.0 + happy * 5.0 + smile_strength * 1.4 + max(0.0, score_margin) * 1.3, 1, 10),
        "neutural": clamp(4.2 + face_quality * 1.1 + max(0.0, score_margin) * 0.6, 1, 10),
        "sad": clamp(4.0 + sad * 4.8 + dark_score * 1.2 + max(0.0, score_margin) * 1.1, 1, 10),
        "anxious": clamp(4.1 + anxious * 4.9 + max(0.0, score_margin) * 1.2, 1, 10),
        "tired": clamp(4.0 + tired * 4.5 + blur_score * 1.0 + dark_score * 0.7, 1, 10),
    }

    return (
        {
            "type": dominant_type,
            "intensity": intensities[dominant_type],
            "confidence": confidence,
            "summary": summaries[dominant_type],
        },
        metrics,
    )


def detect_metrics(image_path: Path) -> tuple[dict, dict]:
    image = cv2.imread(str(image_path))
    if image is None:
        raise RuntimeError("The captured image could not be opened.")
    image = resize_if_needed(image)

    face_image, region = detect_primary_face(image)
    face_image, eyes = align_face(face_image)
    quality = compute_image_quality(face_image)
    features = compute_facial_features(face_image, eyes)
    emotions = infer_emotions(features, quality)

    result, metrics = classify(emotions, quality, features)
    metrics["dominant_emotion_raw"] = round(
        max(
            emotions["happy"],
            emotions["neutral"],
            emotions["sad"],
            emotions["angry"],
            emotions["fear"],
            emotions["disgust"],
            emotions["surprise"],
        )
        / 100.0,
        4,
    )
    metrics["face_x"] = int(region["x"])
    metrics["face_y"] = int(region["y"])
    metrics["face_w"] = int(region["w"])
    metrics["face_h"] = int(region["h"])
    return result, metrics


def main(argv):
    if len(argv) != 2:
        return emit_error("Usage: emotion_detector.py <image_path>", 1)

    image_path = Path(argv[1])
    if not image_path.is_file():
        return emit_error("The image file to analyze was not found.", 1)

    try:
        result, metrics = detect_metrics(image_path)
    except ValueError as error:
        return emit_error(str(error), 2)
    except Exception as error:
        return emit_error(str(error), 1)

    result["metrics"] = metrics
    print(json.dumps(result))
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv))
