#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import pickle
import pandas as pd
import numpy as np
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from googleapiclient.discovery import build
import json
import sys
import os

# Configuration - TA CLÉ API
API_KEY = "AIzaSyAKVvnEd0IYTM9WTvSmv9ge8cgLUk9StTM"

# Chemins absolus
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DATA_DIR = os.path.join(SCRIPT_DIR, 'data')
DATA_FILE = os.path.join(DATA_DIR, 'videos_data.pkl')

# Stop words français
FRENCH_STOP_WORDS = [
    'le', 'la', 'les', 'un', 'une', 'des', 'du', 'de', 'et', 'ou', 'mais',
    'donc', 'or', 'ni', 'car', 'est', 'sont', 'était', 'étaient', 'sera',
    'pour', 'par', 'avec', 'sans', 'sous', 'sur', 'dans', 'hors', 'entre',
    'je', 'tu', 'il', 'elle', 'on', 'nous', 'vous', 'ils', 'elles', 'me',
    'te', 'se', 'ce', 'cette', 'ces', 'cet', 'au', 'aux', 'ceux', 'celles',
    'que', 'qui', 'quoi', 'dont', 'où', 'lui', 'leur', 'eux', 'cela', 'ça',
    'alors', 'ainsi', 'aussi', 'très', 'trop', 'peu', 'beaucoup', 'plus',
    'moins', 'bien', 'mal', 'même', 'toujours', 'jamais', 'encore', 'déjà'
]

class YouTubeRecommender:
    def __init__(self, api_key=None):
        self.api_key = api_key or API_KEY
        self.youtube = None
        self.df = None
        self.tfidf_matrix = None
        self.tfidf_vectorizer = None
        self.data_file = DATA_FILE
        
        if self.api_key:
            try:
                self.youtube = build('youtube', 'v3', developerKey=self.api_key)
                print("✅ API YouTube initialisée", file=sys.stderr)
            except Exception as e:
                print(f"❌ Erreur API: {e}", file=sys.stderr)
    
    def fetch_videos(self, query="développement personnel", max_results=30):
        """Récupère des vidéos YouTube via l'API"""
        if not self.youtube:
            print("❌ API non initialisée", file=sys.stderr)
            return []
        
        videos_data = []
        
        try:
            print(f"🔍 Recherche de vidéos pour: {query}", file=sys.stderr)
            
            request = self.youtube.search().list(
                part="snippet",
                q=query,
                type="video",
                maxResults=min(max_results, 50),
                videoDuration="medium"
            )
            response = request.execute()
            
            print(f"📊 {len(response.get('items', []))} vidéos trouvées", file=sys.stderr)
            
            # Récupérer les IDs des vidéos
            video_ids = [item['id']['videoId'] for item in response.get('items', [])]
            
            # Récupérer les stats
            stats_dict = {}
            if video_ids:
                stats_request = self.youtube.videos().list(
                    part="statistics",
                    id=','.join(video_ids[:50])
                )
                stats_response = stats_request.execute()
                
                for item in stats_response.get('items', []):
                    stats_dict[item['id']] = {
                        'viewCount': int(item['statistics'].get('viewCount', 0)),
                        'likeCount': int(item['statistics'].get('likeCount', 0))
                    }
            
            for item in response.get('items', []):
                try:
                    video_id = item['id']['videoId']
                    title = item['snippet']['title']
                    description = item['snippet']['description']
                    channel_title = item['snippet']['channelTitle']
                    stats = stats_dict.get(video_id, {})
                    
                    videos_data.append({
                        'id': video_id,
                        'title': title,
                        'description': description[:500],
                        'channel': channel_title,
                        'url': f"https://www.youtube.com/watch?v={video_id}",
                        'thumbnail': f"https://img.youtube.com/vi/{video_id}/mqdefault.jpg",
                        'views': stats.get('viewCount', 0),
                        'likes': stats.get('likeCount', 0)
                    })
                except Exception as e:
                    continue
                    
        except Exception as e:
            print(f"❌ Erreur API: {e}", file=sys.stderr)
            return []
        
        return videos_data
    
    def build_matrix(self, videos_data):
        """Construit la matrice TF-IDF"""
        if not videos_data:
            return None
        
        self.df = pd.DataFrame(videos_data)
        
        # Combiner et nettoyer les textes
        self.df['text_features'] = self.df['title'] + " " + self.df['description']
        self.df['text_features'] = self.df['text_features'].str.lower()
        
        # Vectorisation
        self.tfidf_vectorizer = TfidfVectorizer(
            stop_words=FRENCH_STOP_WORDS,
            max_features=3000,
            min_df=1,
            max_df=0.9
        )
        
        self.tfidf_matrix = self.tfidf_vectorizer.fit_transform(self.df['text_features'])
        
        # Sauvegarder
        os.makedirs(DATA_DIR, exist_ok=True)
        with open(self.data_file, 'wb') as f:
            pickle.dump({
                'df': self.df,
                'tfidf_matrix': self.tfidf_matrix,
                'tfidf_vectorizer': self.tfidf_vectorizer
            }, f)
        
        print(f"✅ Données sauvegardées: {len(self.df)} vidéos", file=sys.stderr)
        return self.tfidf_matrix
    
    def load_data(self):
        """Charge les données sauvegardées"""
        if not os.path.exists(self.data_file):
            print(f"⚠️ Fichier non trouvé: {self.data_file}", file=sys.stderr)
            return False
        
        with open(self.data_file, 'rb') as f:
            data = pickle.load(f)
            self.df = data['df']
            self.tfidf_matrix = data['tfidf_matrix']
            self.tfidf_vectorizer = data['tfidf_vectorizer']
        
        print(f"✅ Données chargées: {len(self.df)} vidéos", file=sys.stderr)
        return True
    
    def recommend_by_text(self, text, top_n=8):
        """Recommande des vidéos à partir d'un texte"""
        if self.df is None:
            if not self.load_data():
                return []
        
        try:
            text = text.lower()
            text_vector = self.tfidf_vectorizer.transform([text])
            similarities = cosine_similarity(text_vector, self.tfidf_matrix).flatten()
            
            sim_scores = list(enumerate(similarities))
            sim_scores = sorted(sim_scores, key=lambda x: x[1], reverse=True)
            
            recommendations = []
            for i, score in sim_scores:
                if score > 0.01 or len(recommendations) < top_n:
                    row = self.df.iloc[i]
                    recommendations.append({
                        'id': str(row['id']),
                        'title': str(row['title']),
                        'url': str(row['url']),
                        'thumbnail': str(row.get('thumbnail', '')),
                        'channel': str(row.get('channel', '')),
                        'similarity_score': round(float(score) * 100, 1)
                    })
                    if len(recommendations) >= top_n:
                        break
            
            return recommendations
        except Exception as e:
            print(f"❌ Erreur: {e}", file=sys.stderr)
            return self.get_random_recommendations(top_n)
    
    def get_random_recommendations(self, top_n=8):
        """Retourne des recommandations aléatoires"""
        if self.df is None:
            if not self.load_data():
                return []
        
        n = min(top_n, len(self.df))
        if n == 0:
            return []
        
        sample_df = self.df.sample(n=n)
        recommendations = []
        for _, row in sample_df.iterrows():
            recommendations.append({
                'id': str(row['id']),
                'title': str(row['title']),
                'url': str(row['url']),
                'thumbnail': str(row.get('thumbnail', '')),
                'channel': str(row.get('channel', ''))
            })
        
        return recommendations

if __name__ == "__main__":
    action = sys.argv[1] if len(sys.argv) > 1 else "random"
    recommender = YouTubeRecommender()
    
    if action == "fetch":
        query = sys.argv[2] if len(sys.argv) > 2 else "développement personnel"
        max_results = int(sys.argv[3]) if len(sys.argv) > 3 else 30
        videos = recommender.fetch_videos(query, max_results)
        if videos:
            recommender.build_matrix(videos)
            print(json.dumps({"status": "success", "count": len(videos)}))
        else:
            print(json.dumps({"status": "error", "message": "Aucune vidéo trouvée"}))
    
    elif action == "recommend_by_text":
        text = sys.argv[2] if len(sys.argv) > 2 else ""
        recommendations = recommender.recommend_by_text(text)
        print(json.dumps(recommendations, ensure_ascii=False))
    
    elif action == "random":
        recommendations = recommender.get_random_recommendations()
        print(json.dumps(recommendations, ensure_ascii=False))
    
    else:
        print(json.dumps({"status": "error", "message": f"Action non reconnue: {action}"}))