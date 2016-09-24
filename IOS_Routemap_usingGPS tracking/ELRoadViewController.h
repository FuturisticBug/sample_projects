//
//  ELRoadViewController.h
//  EzyLog
//
//  Created by Rahul Singha Roy on 18/06/15.
//  Copyright (c) 2015 Esolz. All rights reserved.
//

#import <UIKit/UIKit.h>
#import <MapKit/MapKit.h>
#import "Follower.h"
#import "MetricView.h"
#import "MACircleProgressIndicator.h"

@interface ELRoadViewController : UIViewController<MKMapViewDelegate, FollowerDelegate>
@property (weak, nonatomic) IBOutlet MKMapView *mapview;

@property (weak, nonatomic) IBOutlet UILabel *quietstreetlabel;


@property (nonatomic, strong) Follower *follower;

@property (nonatomic, strong) MKMapView *mapView;
@property (nonatomic, strong) UIView *maskView;
@property (nonatomic, strong) UIImageView *avatarImageView;
@property (nonatomic, strong) UILabel *dateLabel;
@property (nonatomic, strong) UIImageView *cycleImageView;
@property (nonatomic, strong) UIButton *trackingButton;
@property (weak, nonatomic) IBOutlet UIImageView *backImage;

@property (nonatomic, strong) UIView *containerView;
@property (nonatomic, strong) MetricView *timeView;
@property (nonatomic, strong) MetricView *topSpeedView;
@property (nonatomic, strong) MetricView *averageSpeedView;
@property (nonatomic, strong) MetricView *distanceView;
@property (nonatomic, strong) MetricView *averageAltitudeView;
@property (nonatomic, strong) MetricView *maxAltitudeView;

@property (weak, nonatomic) IBOutlet MKMapView *driver_map;

@property (weak, nonatomic) IBOutlet UILabel *total_time;

@property (weak, nonatomic) IBOutlet UILabel *total_distance;
@property (weak, nonatomic) IBOutlet UILabel *speed;
@property (weak, nonatomic) IBOutlet UIView *drive_base_view;

@property(nonatomic,strong)NSString *background;



@property (weak, nonatomic) IBOutlet UILabel *driver_hours_driven;

@property (weak, nonatomic) IBOutlet UILabel *driver_distance_driven;

@property (weak, nonatomic) IBOutlet UILabel *driver_avg_speed;

@property(nonatomic)float difFloat;

@property (strong, nonatomic) IBOutlet MACircleProgressIndicator *smallProgressIndicator;

- (IBAction)End_Drive_Action:(id)sender;

@end
