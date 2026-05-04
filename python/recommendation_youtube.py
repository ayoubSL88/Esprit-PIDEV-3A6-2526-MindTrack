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

# Configuration
API_KEY = "AIzaSyA_-YBwEAP4lkkrRSXgq1cqs6TcHLcnJxM"

class YouTubeRecommender:
    def __init__(self, api_key=None):
        self.api_key = api_key or API_KEY
        self.youtube = None
        self.df = None
        self.tfidf_matrix = None
        self.tfidf_vectorizer = None
        self.data_file = os.path.join(os.path.dirname(__file__), 'data', 'videos_data.pkl')
        
        # Initialiser l'API YouTube
        if self.api_key:
            try:
                self.youtube = build('youtube', 'v3', developerKey=self.api_key)
                print("✅ API YouTube initialisée avec succès", file=sys.stderr)
            except Exception as e:
                print(f"❌ Erreur d'initialisation API: {e}", file=sys.stderr)
    
    def fetch_videos(self, query="développement personnel", max_results=50, load_more=False):
        """Récupère des vidéos YouTube via l'API"""
        if not self.youtube:
            print("❌ API non initialisée", file=sys.stderr)
            return []
        
        videos_data = []
        next_page_token = None
        
        # Catégories pertinentes pour le développement personnel
        categories = [
            "développement personnel",
            "méditation guidée",
            "gestion du stress",
            "confiance en soi",
            "motivation quotidienne",
            "pleine conscience",
            "bien-être mental",
            "psychologie positive",
            "coaching personnel",
            "habitudes productives"
        ]
        
        # Si la requête est générique, utiliser plusieurs catégories
        if query == "développement personnel" and max_results > 30:
            all_videos = []
            for cat in categories[:3]:  # Limiter pour éviter trop d'appels
                try:
                    request = self.youtube.search().list(
                        part="snippet",
                        q=cat,
                        type="video",
                        maxResults=min(20, max_results),
                        videoDuration="medium"
                    )
                    response = request.execute()
                    all_videos.extend(response.get('items', []))
                except Exception as e:
                    print(f"Erreur pour la catégorie {cat}: {e}", file=sys.stderr)
            response = {'items': all_videos[:max_results]}
        else:
            try:
                request = self.youtube.search().list(
                    part="snippet",
                    q=query,
                    type="video",
                    maxResults=min(max_results, 50),
                    pageToken=next_page_token,
                    videoDuration="medium"
                )
                response = request.execute()
            except Exception as e:
                print(f"Erreur API: {e}", file=sys.stderr)
                return []
        
        # Récupérer les IDs des vidéos pour les stats
        video_ids = [item['id']['videoId'] for item in response.get('items', [])]
        
        # Récupérer les stats détaillées
        stats_dict = {}
        if video_ids:
            try:
                # Traiter par lots de 50
                for i in range(0, len(video_ids), 50):
                    batch_ids = video_ids[i:i+50]
                    stats_request = self.youtube.videos().list(
                        part="statistics,snippet",
                        id=','.join(batch_ids)
                    )
                    stats_response = stats_request.execute()
                    
                    for item in stats_response.get('items', []):
                        stats_dict[item['id']] = {
                            'viewCount': int(item['statistics'].get('viewCount', 0)),
                            'likeCount': int(item['statistics'].get('likeCount', 0)),
                            'commentCount': int(item['statistics'].get('commentCount', 0))
                        }
            except Exception as e:
                print(f"Erreur récupération stats: {e}", file=sys.stderr)
        
        for item in response.get('items', []):
            try:
                video_id = item['id']['videoId']
                title = item['snippet']['title']
                description = item['snippet']['description']
                channel_title = item['snippet']['channelTitle']
                
                stats = stats_dict.get(video_id, {})
                
                # Calculer un score d'engagement
                views = stats.get('viewCount', 0)
                likes = stats.get('likeCount', 0)
                engagement = round((likes / max(views, 1)) * 100, 1) if views > 0 else 0
                
                videos_data.append({
                    'id': video_id,
                    'title': title,
                    'description': description[:500],  # Limiter la description
                    'channel': channel_title,
                    'url': f"https://www.youtube.com/watch?v={video_id}",
                    'thumbnail': f"https://img.youtube.com/vi/{video_id}/mqdefault.jpg",
                    'views': views,
                    'likes': likes,
                    'engagement': engagement,
                    'comments': stats.get('commentCount', 0)
                })
            except Exception as e:
                print(f"Erreur traitement vidéo: {e}", file=sys.stderr)
                continue
        
        return videos_data
    
    def build_matrix(self, videos_data):
        """Construit la matrice TF-IDF à partir des vidéos"""
        if not videos_data:
            print("❌ Aucune donnée vidéo à traiter", file=sys.stderr)
            return None
        
        # Créer le DataFrame
        self.df = pd.DataFrame(videos_data)
        
        # Combiner titre et description
        self.df['text_features'] = self.df['title'] + " " + self.df['description']
        
        # Nettoyer les titres (enlever les caractères spéciaux)
        self.df['text_features'] = self.df['text_features'].str.replace(r'[^\w\s]', ' ', regex=True)
        self.df['text_features'] = self.df['text_features'].str.lower()
        
        # Vectorisation TF-IDF
        self.tfidf_vectorizer = TfidfVectorizer(
            stop_words='french',
            max_features=3000,
            min_df=2,
            max_df=0.8
        )
        
        self.tfidf_matrix = self.tfidf_vectorizer.fit_transform(self.df['text_features'])
        
        # Sauvegarder les données
        self.save_data()
        
        print(f"✅ Matrice TF-IDF construite: {self.tfidf_matrix.shape}", file=sys.stderr)
        
        return self.tfidf_matrix
    
    def save_data(self):
        """Sauvegarde les données pour une utilisation ultérieure"""
        try:
            os.makedirs(os.path.dirname(self.data_file), exist_ok=True)
            with open(self.data_file, 'wb') as f:
                pickle.dump({
                    'df': self.df,
                    'tfidf_matrix': self.tfidf_matrix,
                    'tfidf_vectorizer': self.tfidf_vectorizer
                }, f)
            print(f"✅ Données sauvegardées dans {self.data_file}", file=sys.stderr)
        except Exception as e:
            print(f"❌ Erreur sauvegarde: {e}", file=sys.stderr)
    
    def load_data(self):
        """Charge les données sauvegardées"""
        if not os.path.exists(self.data_file):
            print(f"⚠️ Fichier de données non trouvé: {self.data_file}", file=sys.stderr)
            return False
        
        try:
            with open(self.data_file, 'rb') as f:
                data = pickle.load(f)
                self.df = data['df']
                self.tfidf_matrix = data['tfidf_matrix']
                self.tfidf_vectorizer = data['tfidf_vectorizer']
            print(f"✅ Données chargées: {len(self.df)} vidéos", file=sys.stderr)
            return True
        except Exception as e:
            print(f"❌ Erreur chargement: {e}", file=sys.stderr)
            return False
    
    def recommend_by_video_id(self, video_id, top_n=6):
        """Recommande des vidéos similaires à partir d'un ID de vidéo"""
        if self.df is None:
            if not self.load_data():
                return []
        
        # Trouver l'index de la vidéo
        video_indices = self.df[self.df['id'] == video_id].index
        if len(video_indices) == 0:
            return []
        
        idx = video_indices[0]
        
        # Calculer les similarités
        sim_scores = list(enumerate(self.tfidf_matrix[idx].toarray()[0]))
        sim_scores = sorted(sim_scores, key=lambda x: x[1], reverse=True)
        
        # Exclure la vidéo elle-même
        sim_scores = [s for s in sim_scores if s[1] < 0.999][:top_n]
        
        # Récupérer les recommandations
        recommendations = []
        for i, score in sim_scores:
            if i < len(self.df):
                row = self.df.iloc[i]
                recommendations.append({
                    'id': row['id'],
                    'title': row['title'],
                    'url': row['url'],
                    'thumbnail': row.get('thumbnail', ''),
                    'channel': row.get('channel', ''),
                    'similarity_score': round(float(score) * 100, 1),
                    'views': row.get('views', 0),
                    'engagement': row.get('engagement', 0)
                })
        
        return recommendations
    
    def recommend_by_text(self, text, top_n=6):
        """Recommande des vidéos à partir d'un texte (recherche)"""
        if self.df is None:
            if not self.load_data():
                return []
        
        # Vectoriser le texte de recherche
        try:
            text_vector = self.tfidf_vectorizer.transform([text])
            
            # Calculer la similarité avec toutes les vidéos
            similarities = cosine_similarity(text_vector, self.tfidf_matrix).flatten()
            
            # Trier par similarité
            sim_scores = list(enumerate(similarities))
            sim_scores = sorted(sim_scores, key=lambda x: x[1], reverse=True)
            
            # Garder les meilleurs résultats
            sim_scores = sim_scores[:top_n]
            
            recommendations = []
            for i, score in sim_scores:
                if score > 0.05:  # Seuil minimum de pertinence
                    row = self.df.iloc[i]
                    recommendations.append({
                        'id': row['id'],
                        'title': row['title'],
                        'url': row['url'],
                        'thumbnail': row.get('thumbnail', ''),
                        'channel': row.get('channel', ''),
                        'similarity_score': round(float(score) * 100, 1),
                        'views': row.get('views', 0)
                    })
            
            return recommendations
        except Exception as e:
            print(f"❌ Erreur recommandation: {e}", file=sys.stderr)
            return []
    
    def get_random_recommendations(self, top_n=6):
        """Retourne des recommandations aléatoires"""
        if self.df is None:
            if not self.load_data():
                return []
        
        # Prioriser les vidéos avec un bon engagement
        if 'engagement' in self.df.columns:
            high_engagement_df = self.df[self.df['engagement'] > 5]
            if len(high_engagement_df) >= top_n:
                sample_df = high_engagement_df.sample(n=top_n)
            else:
                sample_df = self.df.sample(n=min(top_n, len(self.df)))
        else:
            sample_df = self.df.sample(n=min(top_n, len(self.df)))
        
        recommendations = []
        for _, row in sample_df.iterrows():
            recommendations.append({
                'id': row['id'],
                'title': row['title'],
                'url': row['url'],
                'thumbnail': row.get('thumbnail', ''),
                'channel': row.get('channel', ''),
                'engagement': row.get('engagement', 0)
            })
        
        return recommendations

# Point d'entrée pour l'exécution en ligne de commande
if __name__ == "__main__":
    action = sys.argv[1] if len(sys.argv) > 1 else "recommend"
    
    recommender = YouTubeRecommender()
    
    if action == "fetch":
        query = sys.argv[2] if len(sys.argv) > 2 else "développement personnel"
        max_results = int(sys.argv[3]) if len(sys.argv) > 3 else 50
        print(f"🔍 Récupération des vidéos pour: {query}", file=sys.stderr)
        videos = recommender.fetch_videos(query, max_results)
        if videos:
            recommender.build_matrix(videos)
            print(json.dumps({"status": "success", "count": len(videos)}))
        else:
            print(json.dumps({"status": "error", "message": "Aucune vidéo trouvée"}))
    
    elif action == "recommend_by_text":
        text = sys.argv[2] if len(sys.argv) > 2 else ""
        print(f"🔍 Recherche de vidéos pour: {text}", file=sys.stderr)
        recommendations = recommender.recommend_by_text(text)
        print(json.dumps(recommendations, ensure_ascii=False))
    
    elif action == "recommend_by_id":
        video_id = sys.argv[2] if len(sys.argv) > 2 else ""
        recommendations = recommender.recommend_by_video_id(video_id)
        print(json.dumps(recommendations, ensure_ascii=False))
    
    elif action == "random":
        recommendations = recommender.get_random_recommendations()
        print(json.dumps(recommendations, ensure_ascii=False))
    
    elif action == "stats":
        if recommender.load_data():
            print(json.dumps({
                "status": "success",
                "total_videos": len(recommender.df),
                "categories": list(recommender.df['channel'].value_counts().head(10).to_dict())
            }, ensure_ascii=False))
        else:
            print(json.dumps({"status": "error", "message": "Aucune données"}))
    else:
        print(json.dumps({"status": "error", "message": f"Action non reconnue: {action}"}))