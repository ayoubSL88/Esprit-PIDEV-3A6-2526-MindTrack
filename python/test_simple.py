import json
import sys

# Données de test
test_videos = [
    {"id": "1", "title": "Méditation guidée pour débutants", "url": "https://youtube.com/watch?v=1", "thumbnail": "", "channel": "Bien-être", "similarity_score": 95},
    {"id": "2", "title": "10 min de méditation pleine conscience", "url": "https://youtube.com/watch?v=2", "thumbnail": "", "channel": "Zen", "similarity_score": 88},
    {"id": "3", "title": "Gérer son stress par la méditation", "url": "https://youtube.com/watch?v=3", "thumbnail": "", "channel": "Santé Mentale", "similarity_score": 82},
]

if len(sys.argv) > 1 and sys.argv[1] == "recommend_by_text":
    print(json.dumps(test_videos))
else:
    print(json.dumps(test_videos))